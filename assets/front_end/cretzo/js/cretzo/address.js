$(document).ready(function() {
    setupActionButtons();
    checkForAddOrEditAddressInQuery();
    /* Both add and edit modals share the same pincode-first layout, so wire the
       auto-fill up for each of them. */
    setupPincodeAutofill('#pincode', '#city_name', '#district', '#state', '#pincode_status');
    setupPincodeAutofill('#edit_pincode', '#edit_city_name', '#edit_district', '#edit_state', '#edit_pincode_status');
});

/* Pincode flow: the user enters the pincode first and city / district / state
   are auto-filled from the India Post public API. All three stay editable so the
   user can correct anything the lookup gets wrong. Used by both the add and edit
   address modals - pass the selectors for the modal being wired up. */
function setupPincodeAutofill(pincodeSelector, citySelector, districtSelector, stateSelector, statusSelector) {
    var $pincode = $(pincodeSelector);
    var $city = $(citySelector);
    var $district = $(districtSelector);
    var $state = $(stateSelector);
    var $status = $(statusSelector);
    if (!$pincode.length) {
        return;
    }

    function setState(stateName) {
        if (!stateName) {
            return;
        }
        var target = stateName.toLowerCase().trim();
        $state.find('option').each(function () {
            if ($(this).val().toLowerCase().trim() === target) {
                $state.val($(this).val());
                return false;
            }
        });
    }

    $pincode.on('input', function () {
        // keep digits only
        var pin = $pincode.val().replace(/\D/g, '');
        if (pin !== $pincode.val()) {
            $pincode.val(pin);
        }
        if (pin.length !== 6) {
            $status.text('').removeClass('text-danger text-success');
            return;
        }

        $status.text('Looking up pincode...').removeClass('text-danger text-success');
        $.ajax({
            type: 'GET',
            url: 'https://api.postalpincode.in/pincode/' + pin,
            dataType: 'json',
            success: function (result) {
                var record = (result && result.length) ? result[0] : null;
                if (!record || record.Status !== 'Success' || !record.PostOffice || !record.PostOffice.length) {
                    $status.text('Pincode not found. Please fill the details manually.').addClass('text-danger');
                    return;
                }
                var po = record.PostOffice[0];
                var cityName = (po.Block && po.Block !== 'NA') ? po.Block : po.District;
                $city.val(cityName || '');
                $district.val(po.District || '');
                setState(po.State);
                $status.text('Details filled automatically. You can edit them if needed.').addClass('text-success');
            },
            error: function () {
                $status.text('Could not fetch pincode details. Please fill the details manually.').addClass('text-danger');
            }
        });
    });
}

function checkForAddOrEditAddressInQuery(){
    const urlParams = new URLSearchParams(window.location.search);

    /* if edit address */
    const idParam = urlParams.get("id");
    if (idParam) {
        $(".address-action-btn-edit").each(function () {
            const rowData = $(this).data("row"); // Get the row data from `data-row`
            if (rowData.id == idParam) {
                $(this).click(); // Trigger click event on the matching edit button
            }
        });
        return;
    }

    /* if add address */
    if (urlParams.get("action") === "add") {
        $(".add-address-btn").click();
        return;
    }
}

function setupActionButtons(){

    /* Edit Address */
    $('.address-action-btn.address-action-btn-edit').click(function(e) {
        e.preventDefault();

        var row = $(this).data("row");
        updateEditAddressForm(row);
        CzAccount.open('#edit-address-modal');
    });

    /* Set As Default Address */
    $('.address-action-btn.address-action-btn-default').click(function (e) {
        e.preventDefault();
        var id = $(this).data("id");

        /* Was window.confirm(). That dialog cannot be styled, does not say WHICH
           address it is about, and some mobile browsers suppress it after a few
           uses - which turned this button into a no-op with no feedback at all. */
        CzAccount.confirm({
            title: 'Make this your default address?',
            text: 'New orders will be delivered here unless you pick another address at checkout.',
            confirmText: 'Set as default',
            icon: 'uil-check-circle'
        }).then(function (ok) {
            if (!ok) {
                return;
            }
            $.ajax({
                type: "POST",
                data: {
                    id: id,
                    [csrfName]: csrfHash
                },
                url: base_url + "my-account/set-default-address",
                dataType: "json",
                success: function (res) {
                    csrfName = res.csrfName;
                    csrfHash = res.csrfHash;
                    if (res.error == 0) {
                        Toast.fire({ icon: "success", title: res.message });
                        window.location.reload();
                    } else {
                        Toast.fire({ icon: "error", title: res.message });
                    }
                },
                /* Without this a rejected POST (403 on an expired session, 500)
                   did nothing visible whatsoever. */
                error: function (xhr) {
                    Toast.fire({
                        icon: "error",
                        title: xhr.status === 403
                            ? "Your session expired. Please reload the page."
                            : "Could not update the address. Please try again."
                    });
                }
            });
        });
    });

    /* Delete Address */
    $('.address-action-btn.address-action-btn-remove').click(function (e) {
        e.preventDefault();
        var id = $(this).data("id");
        var name = $(this).data("name");

        CzAccount.confirm({
            title: 'Remove this address?',
            text: (name ? '"' + name + '" ' : 'This address ') +
                'will be deleted from your account. Orders already placed to it are not affected.',
            confirmText: 'Remove address',
            tone: 'danger',
            icon: 'uil-trash-alt'
        }).then(function (ok) {
            if (!ok) {
                return;
            }
            $.ajax({
                type: "POST",
                data: {
                    id: id,
                    [csrfName]: csrfHash
                },
                url: base_url + "my-account/delete-address",
                dataType: "json",
                success: function (res) {
                    csrfName = res.csrfName;
                    csrfHash = res.csrfHash;
                    if (res.error == 0) {
                        Toast.fire({ icon: "success", title: res.message || "Address removed." });
                        window.location.reload();
                    } else {
                        Toast.fire({ icon: "error", title: res.message });
                    }
                },
                error: function (xhr) {
                    Toast.fire({
                        icon: "error",
                        title: xhr.status === 403
                            ? "Your session expired. Please reload the page."
                            : "Could not remove the address. Please try again."
                    });
                }
            });
        });
    });
}

function updateEditAddressForm(row){

    /* Reset form fields before filling with a new one */
    $("#edit-address-form")[0].reset();

    $("#address_id").val(row.id);
    $("#edit_name").val(row.name);
    $("#edit_mobile").val(row.mobile);
    $("#edit_alternate_mobile").val(row.alternate_mobile || '');
    $("#edit_address").val(row.address);
    $("#edit_landmark").val(row.landmark || '');
    /* city column holds the city/block, area column holds the district - matching
       how the add-address form maps its City and District fields. */
    $("#edit_city_name").val(row.city || '');
    $("#edit_city").val(row.city_id || '');
    $("#edit_district").val(row.area || '');
    $("#edit_state").val(row.state);
    $("#edit_country").val(row.country || 'India');
    $("#edit_pincode").val(row.pincode);

    /* clear any pincode helper text left over from a previous edit */
    $("#edit_pincode_status").text('').removeClass('text-danger text-success');

    /* The address-type pills read their state from a .is-checked class that
       account-suite.js mirrors off the `change` event. .prop("checked") does not
       fire one, so both the input and the pill are set here - otherwise the
       stored type was selected in the form but no pill looked selected. */
    $('#edit-address-form .czap-radio').removeClass('is-checked');
    if (row.type) {
        $('#edit-address-form input[type=radio][value=' + row.type.toLowerCase() + ']')
            .prop('checked', true)
            .closest('.czap-radio').addClass('is-checked');
    }
}

$("#edit-address-form").on("submit", function (e) {
    e.preventDefault();
    var t = new FormData(this);

    t.append(csrfName, csrfHash), $.ajax({
        type: "POST",
        data: t,
        url: $(this).attr("action"),
        dataType: "json",
        cache: !1,
        contentType: !1,
        processData: !1,
        beforeSend: function () {
            $("#edit-address-submit-btn").val("Please Wait...").attr("disabled", !0)
        },
        success: function (e) {
            csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error 
                ?
                    (
                        $("#edit-address-result").html("<div class='alert alert-success'>" + e.message + "</div>").show().delay(1500).fadeOut(), 
                        // $("#edit-address-form")[0].reset(), 
                        // $("#address_list_table").bootstrapTable("refresh"), 
                        setTimeout(function () {
                            CzAccount.close('#edit-address-modal');
                            $("#edit-address-form")[0].reset();
                            
                            // Check if the URL contains 'redirect=cart'
                            const urlParams = new URLSearchParams(window.location.search);
                            if (urlParams.get("redirect") === "checkout") {
                                window.location.href = base_url + 'cart/checkout?id=' + urlParams.get("id");
                            } else {
                                window.location.reload();
                            }

                            // since the above line for hiding modal isn't working, we are adding this for now (cretzo):
                            // $("#edit-address-modal button.close").click();
                        }, 2e3)
                    ) 
                : 
                    (
                        $("#edit-address-result").html("<div class='alert alert-danger'>" + e.message + "</div>").show().delay(1500).fadeOut(), 
                        $("#edit-address-submit-btn").val("Save").attr("disabled", !1)
                    )
        }
    })
})

$("#add-address-form").on("submit", function (e) {
    e.preventDefault();
    var t = new FormData(this);
    t.append(csrfName, csrfHash), $.ajax({
        type: "POST",
        data: t,
        url: $(this).attr("action"),
        dataType: "json",
        cache: !1,
        contentType: !1,
        processData: !1,
        beforeSend: function () {
            $("#save-address-submit-btn").val("Please Wait...").attr("disabled", !0)
        },
        success: function (e) {
            csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error 
            ? 
                (
                    $("#save-address-result").html("<div class='alert alert-success'>" + e.message + "</div>").show().delay(1500).fadeOut(), 
                    // $("#add-address-form")[0].reset(), 
                    // $("#address_list_table").bootstrapTable("refresh"),
                    setTimeout(function () {
                        CzAccount.close('#add-address-modal');
                        $("#add-address-form")[0].reset();

                        // Check if the URL contains 'redirect=cart'
                        const urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.get("redirect") === "checkout") {
                            window.location.href = base_url + 'cart/checkout?id=' + e.data[0]['id'];
                        } else {
                            window.location.reload();
                        }

                        // since the above line for hiding modal isn't working, we are adding this for now (cretzo):
                        // $("#add-address-modal button.close").click();
                    }, 2e3)
                )
            :
                (
                    $("#save-address-result").html("<div class='alert alert-danger'>" + e.message + "</div>").show().delay(1500).fadeOut(),
                    $("#save-address-submit-btn").val("Save").attr("disabled", !1)
                )
        }
    })
});

// Defensive cleanup for the address modals: Bootstrap's own hide can leave a
// .modal-backdrop and body.modal-open (overflow:hidden) behind, which freezes
// page scrolling after you dismiss the add/edit-address modal. Remove any orphan.
$(document).on('hidden.bs.modal', '#add-address-modal, #edit-address-modal', function () {
    if (!$('.modal.show').length) {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css({ 'overflow': '', 'padding-right': '' });
    }
});