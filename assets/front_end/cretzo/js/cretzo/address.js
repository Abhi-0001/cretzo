$(document).ready(function() {
    setupActionButtons();
    checkForAddOrEditAddressInQuery();
});

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
        $("#edit-address-modal").modal('show');
    });

    /* Set As Default Address */
    $('.address-action-btn.address-action-btn-default').click(function(e) {
        e.preventDefault(), confirm("Are you sure ? You want to set this address as default?") && $.ajax({
            type: "POST",
            data: {
                id: $(this).data("id"),
                [csrfName]: csrfHash
            },
            url: base_url + "my-account/set-default-address",
            dataType: "json",
            success: function (e) {
                csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? (Toast.fire({
                    icon: "success",
                    title: e.message
                }),
                window.location.reload()
                /* , setTimeout(function () {
                    window.location.reload()
                }, 1e3) */
                ) : Toast.fire({
                    icon: "error",
                    title: e.message
                })
            }
        })
    });

    /* Delete Address */
    $('.address-action-btn.address-action-btn-remove').click(function(e) {
        e.preventDefault(), confirm("Are you sure ? You want to delete this address?") && $.ajax({
            type: "POST",
            data: {
                id: $(this).data("id"),
                [csrfName]: csrfHash
            },
            url: base_url + "my-account/delete-address",
            dataType: "json",
            success: function (e) {
                csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? window.location.reload() : Toast.fire({
                    icon: "error",
                    title: e.message
                })
            }
        })    
    });
}

function updateEditAddressForm(row){

    /* Reset form fields before filling with a new one */
    $("#edit-address-form")[0].reset();
    $('#edit-address-form .form-select2').val('').trigger("change");

    $("#address_id").val(row.id);
    $("#edit_name").val(row.name);
    $("#edit_area").val(row.area);
    // $("#edit_area").empty();
    $("#edit_mobile").val(row.mobile);
    $("#edit_address").val(row.address);
    $("#edit_state").val(row.state);
    $("#edit_country").val(row.country);
    $("#edit_pincode").val(row.pincode);
    
    if (row.city_id == 0 || row.city_id == "") {
        $('.edit_area').addClass('d-none');
        // $('.edit_city').addClass('d-none');
        $('.edit_pincode').addClass('d-none');
        // $('.other_areas').removeClass('d-none');
        $("#other_areas_value").val(row.area);
        // $('.other_city').removeClass('d-none');
        $("#other_city_value").val(row.area);
        $('.other_pincode').removeClass('d-none');
        $("#other_pincode_value").val(row.pincode);
        $("#edit_city").val(row.city_id);
    } else if (row.system_pincode == 0) {
        $("#edit_city").val(row.city_id).trigger('change', [row.pincode]);
        // $('.edit_pincode').addClass('d-none');
        
        $('.other_pincode').removeClass('d-none');
        $("#other_pincode_value").val(row.pincode);
    } else {
        $("#edit_city").val(row.city_id).trigger('change', [row.pincode]);

        $("#edit_pincode").val(0).trigger('change');
        $('.other_pincode').addClass('d-none');
        $("#other_pincode_value").val('');
    }
    if(row.type !="")
    {
        $('input[type=radio][value=' + row.type.toLowerCase() + ']').attr('checked', true);
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
                            $("#edit-address-modal").modal("hide");
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
                        $("#add-address-modal").modal("hide");
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