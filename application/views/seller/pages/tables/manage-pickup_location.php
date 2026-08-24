<style>
    /* Address/Address2 held long, comma-heavy text that wrapped onto several lines and made
       every row of this table tall. Truncating to one line with an ellipsis keeps rows compact;
       the title attribute (set in the post-body handler below) still surfaces the full text
       on hover. */
    #pickup_locations_table td,
    #pickup_locations_table th {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 220px;
    }

    /* bootstrap-table doesn't put data-field on rendered <td>s, only on the <th> definitions -
       Actions is always the last column, so target it positionally instead. */
    #pickup_locations_table td:last-child {
        overflow: visible;
        max-width: none;
    }
</style>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Manage Pickup Location</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Pickup Location</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-info">
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" action="<?= base_url('seller/pickup_location/add_pickup_location'); ?>" method="POST" id="add_pickup_location_form" enctype="multipart/form-data">
                            <?php
                            if (isset($fetched_data[0]['id'])) {
                            ?>
                                <input type="hidden" id="edit_pickup_location" name="edit_pickup_location" value="<?= @$fetched_data[0]['id'] ?>">
                                <input type="hidden" id="update_id" name="update_id" value="1">
                            <?php
                            }
                            ?>

                            <div class="card-body">
                                <div class="form-group row">
                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">Pickup Location <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="pickup_location" placeholder="The nickname of the new pickup location. Max 36 characters." id="pickup_location" value="<?= (isset($fetched_data[0]['pickup_location']) ? $fetched_data[0]['pickup_location'] : '') ?>">
                                    </div>

                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">Name <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="name" placeholder="The shipper's name." id="name" value="<?= (isset($fetched_data[0]['name']) ? $fetched_data[0]['name'] : '') ?>">
                                    </div>

                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">Email <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="email" placeholder="The shipper's email address." id="email" value="<?= (isset($fetched_data[0]['email']) ? $fetched_data[0]['email'] : '') ?>">
                                    </div>

                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">Phone <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="phone" placeholder="Shipper's phone number." id="phone" value="<?= (isset($fetched_data[0]['phone']) ? $fetched_data[0]['phone'] : '') ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">City <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="city" placeholder="Pickup location city name." id="city" value="<?= (isset($fetched_data[0]['city']) ? $fetched_data[0]['city'] : '') ?>">
                                    </div>

                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">State <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="state" placeholder="Pickup location state name." id="state" value="<?= (isset($fetched_data[0]['state']) ? $fetched_data[0]['state'] : '') ?>">
                                    </div>

                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">Country <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="country" placeholder="Pickup location country." id="country" value="<?= (isset($fetched_data[0]['country']) ? $fetched_data[0]['country'] : '') ?>">
                                    </div>

                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">Pincode <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="pincode" placeholder="Pickup location pincode." id="pincode" value="<?= (isset($fetched_data[0]['pin_code']) ? $fetched_data[0]['pin_code'] : '') ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-6">
                                        <label for="area_name" class="control-label col-md-12">Address <span class='text-danger text-xs'>*</span></label>
                                        <textarea class="form-control" name="address" placeholder="Shipper's primary address. Max 80 characters." id="address" value="<?= (isset($fetched_data[0]['address']) ? $fetched_data[0]['address'] : '') ?>"><?= (isset($fetched_data[0]['address']) ? $fetched_data[0]['address'] : '') ?></textarea>
                                        <small class="form-text text-muted">Must include a house/flat/shop no. and road/street name (e.g. "12, Jamia Nagar Road") - Shiprocket rejects addresses without one.</small>
                                    </div>

                                    <div class="col-6">
                                        <label for="area_name" class="control-label col-md-12">Address 2 </label>
                                        <textarea class="form-control" name="address2" placeholder="Additional address details." id="address2" value="<?= (isset($fetched_data[0]['address_2']) ? $fetched_data[0]['address_2'] : '') ?>"><?= (isset($fetched_data[0]['address_2']) ? $fetched_data[0]['address_2'] : '') ?></textarea>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-6">
                                        <label for="area_name" class="control-label col-md-12">Latitude</span></label>
                                        <input type="text" class="form-control" name="latitude" placeholder="Pickup location Latitude." id="latitude" value="<?= (isset($fetched_data[0]['latitude']) ? $fetched_data[0]['latitude'] : '') ?>">
                                    </div>

                                    <div class="col-6">
                                        <label for="area_name" class="control-label col-md-12">Longitude</span></label>
                                        <input type="text" class="form-control" name="longitude" placeholder="Pickup location Longitude." id="longitude" value="<?= (isset($fetched_data[0]['longitude']) ? $fetched_data[0]['longitude'] : '') ?>">
                                    </div>
                                </div>
                                <div class="form-group row"></div>
                                <div class="form-group row">
                                    <div class="col-6">
                                        <button type="reset" class="btn btn-warning">Reset</button>
                                        <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Pickup Location' : 'Add Pickup Location' ?></button>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <div class="form-group d-none" id="error_box"></div>
                                </div>
                            </div><!-- /.card-body -->
                        </form>
                    </div><!--/.card-->
                </div>

            <div class="col-md-12 main-content">
                <div class="card content-area p-4">
                    <div class="card-head">
                        <h4 class="card-title">Pickup Location Details</h4>
                    </div>
                    <div class="card-innr">
                        <div class="gaps-1-5x"></div>
                     
                        <table id="pickup_locations_table" class='table-striped' data-toggle="table" data-url="<?= base_url('seller/pickup_location/view_pickup_location') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-export-types='["txt","excel"]' data-export-options='{
                        "fileName": "area-list",
                        "ignoreColumn": ["operate"] 
                        }' data-maintain-selected="true" data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th data-field="id" data-sortable="true">ID</th>
                                    <th data-field="seller_id" data-sortable="true" data-visible='false'>Seller ID</th>
                                    <th data-field="pickup_location" data-sortable="true">Pickup Locations</th>
                                    <th data-field="name" data-sortable="true">Name</th>
                                    <th data-field="email" data-sortable="true">Email</th>
                                    <th data-field="phone" data-sortable="true">Phone</th>
                                    <th data-field="address">Address</th>
                                    <th data-field="address2">Address 2</th>
                                    <th data-field="city" data-sortable="true">City</th>
                                    <th data-field="pin_code" data-sortable="true">Pincode</th>
                                    <th data-field="operate">Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div><!-- .card-innr -->
                </div><!-- .card -->
            </div>
        </div>
        <!-- /.row -->
</div><!-- /.container-fluid -->
</section>
<!-- /.content -->
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
    // Every column is truncated to one line by default (see the <style> block above) to keep
    // rows compact, but that hid the actual street address behind "..." - the one column
    // sellers actually need to read in full. Address/Address2 are exempted here (by data-field,
    // not column position, so this keeps working if columns are reordered) so they size to
    // their own content instead of being clipped, while everything else stays truncated with a
    // hover tooltip carrying the full text.
    $('#pickup_locations_table').on('post-body.bs.table', function () {
        var $table = $(this);
        var fullTextFields = ['address', 'address2'];

        $table.find('thead th').each(function (index) {
            if (fullTextFields.indexOf($(this).data('field')) === -1) {
                return;
            }
            $table.find('tbody tr').each(function () {
                $(this).find('td').eq(index).css({
                    'overflow': 'visible',
                    'text-overflow': 'unset',
                    'max-width': 'none'
                });
            });
        });

        $table.find('tbody td').each(function () {
            var text = $(this).text().trim();
            if (text) {
                $(this).attr('title', text);
            }
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