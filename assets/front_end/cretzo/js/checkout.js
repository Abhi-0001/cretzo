"use strict";
var stripe1;
var fatoorah_url = '';
$(document).ready(function () {
    var addresses = [];

    $('#documents').on('change', function () {
        var allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        var selectedFiles = this.files;

        for (var i = 0; i < selectedFiles.length; i++) {
            var file = selectedFiles[i];
            var extension = file.name.split('.').pop().toLowerCase();

            if (allowedExtensions.indexOf(extension) === -1) {
                alert('Invalid file format: ' + file.name);
                $('#documents').val('');
                return false;
            }
        }
    });

    $("input[name='payment_method']").on('change', function (e) {
        e.preventDefault();
        var payment_method = $("input[name=payment_method]:checked").val();
        $('#stripe_div').slideUp();
    });
    
    // document.getElementById("address-modal").addEventListener("show.bs.modal", (e) => {
    $('#address-modal').on('show.bs.modal', function (e) {
        $.ajax({
            type: 'POST',
            data: {
                [csrfName]: csrfHash,
            },
            url: base_url + 'my-account/get-address/',
            dataType: 'json',
            success: function (data) {
                csrfName = data.csrfName;
                csrfHash = data.csrfHash;
                var html = '';
                if (data.error == false) {
                    var address_id = $('#address_id').val();
                    var found = 0;
                    $.each(data.data, function (i, e) {
                        var checked = '';
                        if (e.id == address_id) {
                            found = 1;
                            checked = 'checked';
                        } else if (e.is_default == 1 && found == 0) {
                            checked = 'checked';
                        }
                        addresses.push(e);

                        html += '<label for="select-address-' + e.id + '" class="form-check-label"><li class="list-group-item d-flex justify-content-between lh-condensed mt-3">' +
                            '<div class="col-md-1 h-100 my-auto">' +
                            '<input type="radio" class="select-address form-check-input m-0" ' + checked + ' name="select-address" data-index=' + i + ' id="select-address-' + e.id + '" />' +
                            '</div>' +
                            '<div class="row text-start col-11">' +
                            '<div class="text-dark"><i class="fa fa-map-marker"></i> ' + e.name + ' - ' + e.type + '</div>' +
                            '<small class="col-12 text-muted">' + e.area + ' , ' + e.city + ' , ' + e.state + ' , ' + e.country + ' - ' + e.pincode + '</small>' +
                            '<small class="col-12 text-muted">' + e.mobile + '</small>' +
                            '</div>' +
                            '</li></label>';
                    });

                    $('#address-list').html(html);
                }
            }
        })
    })
    // });

    $(document).on('click', '#redeem_promocode', function () {
        event.preventDefault();
        var promo_code = $(this).data('value')
        // $('#promocode_input').val(promo_code);
        $('.promocode_input').val(promo_code);
    })

    document.getElementById("promo-code-modal").addEventListener("show.bs.modal", () => {
        $.ajax({
            type: 'POST',
            data: {
                [csrfName]: csrfHash,
            },
            url: base_url + 'my-account/get_promo_codes/',
            dataType: 'json',
            success: function (data) {

                csrfName = data.csrfName;
                csrfHash = data.csrfHash;
                var html = '';
                if ((data.promo_codes).length != 0) {
                    $.each(data.promo_codes, function (i, e) {
                        html += '<div class="card mb-2"><label for="promo-code-' + e.id + '"><li class="list-group-item d-flex align-item-center mt-3">' +
                            '<div class="promo-code-img"><img src="' + e.image + '" style="max-width:80px;max-height:80px;"/></div>' +
                            '<div class="text-start">' +
                            '<div class="text-dark p-2 copy-promo-code" title="Copy promocode" id="redeem_promocode" data-value = ' + e.promo_code + '>' + e.promo_code + '<i class="fa fa-copy text-blue"></i></div>' +
                            '<small class="text-muted">' + e.message + '</small>' +
                            '</div>' +
                            '</li></label></div>';
                    });
                } else {
                    html += '<div class="col-12 text-dark d-flex justify-content-center">Opps...No Offers Avilable</small>';
                }
                $('#promocode-list').html(html);
            }
        })
    });

    /* --->>> Commented Out Since Not Being Currently Used by Cretzo <<<--- */
    /* $(".address_modal").on('click', '.submit', function (event) {
        event.preventDefault();
        // var address="";
        var index = $('input[class="select-address form-check-input m-0"][type="radio"]:checked').data('index');
        var address = addresses[index];
        var sub_total = $('#sub_total').val();
        sub_total = sub_total.replace(',', '');
        var total = $('#temp_total').val();
        var promocode_amount = $('#promocode_amount').text();
        if (promocode_amount == '') {
            promocode_amount = 0;
        } else {
            promocode_amount = promocode_amount.replace(',', '');
        }
        $('#address-name-type').html(address.name + ' - ' + address.type);
        $('#address-full').html(address.area + ' , ' + address.city);
        $('#address-country').html(address.state + ' , ' + address.country + ' - ' + address.pincode);
        $('#address-mobile').html(address.mobile);
        $('#address_id').val(address.id);
        $('#mobile').val(address.mobile);
        $('.address_modal').modal('hide');
        // $('.address-modal').iziModal('close');
        var address_id = $('#address_id').val();
        $.ajax({
            type: 'POST',
            data: {
                [csrfName]: csrfHash,
                'address_id': address_id,
                'total': total,
            },
            url: base_url + 'cart/get-delivery-charge',
            dataType: 'json',
            success: function (result) {

                alert(JSON.stringify(result));


                csrfName = result.csrfName;
                csrfHash = result.csrfHash;
                var is_time_slots_enabled = 0
                var className = result.error == true ? 'danger' : 'success'
                $('#checkout_form > .row').unblock()
                $('#deliverable_status').html(
                    "<b class='text-" + className + "'>" + result.message + '</b>'
                )
                result.availability_data.forEach(product => {
                    if (product.is_deliverable == false) {
                        $('#p_' + product.product_id).html(
                            "<b class='text-danger'> " +
                            (product.message ?? 'Not deliverable') +
                            '</b>'
                        )
                    } else {
                        $('#p_' + product.product_id).html('')
                    }
                    if (product.delivery_by == 'standard_shipping') {
                        is_time_slots_enabled = 0
                        $('#is_time_slots_enabled').val(is_time_slots_enabled)
                    }
                })

                $('.shipping_method').html(result.shipping_method)
                $('.delivery-charge').html(result.delivery_charge_with_cod)
                $('.delivery-charge').val(result.delivery_charge_with_cod)
                $('.delivery_charge_with_cod').html(result.delivery_charge_with_cod)
                $('.delivery_charge_with_cod').val(result.delivery_charge_with_cod)
                $('.delivery_charge_without_cod').html(result.delivery_charge_without_cod)
                $('.delivery_charge_without_cod').val(result.delivery_charge_without_cod)
                $('.estimate_date').html(result.estimate_date)
                var shipping_method = result.shipping_method
                var delivery_charge = result.delivery_charge_with_cod
                var delivery_charge_with_cod = result.delivery_charge_with_cod
                var delivery_charge_without_cod = result.delivery_charge_without_cod
                result.availability_data.forEach(product => {
                    if (product.delivery_by == 'standard_shipping') {
                        $('.date-time-label').addClass('d-none')
                        $('.date-time-picker').addClass('d-none')
                        $('.time-slot').addClass('d-none')
                    } else {
                        $('.date-time-label').removeClass('d-none')
                        $('.date-time-picker').removeClass('d-none')
                        $('.time-slot').removeClass('d-none')
                    }
                })
                var final_total = parseFloat(sub_total) + parseFloat(delivery_charge);
                var wallet_used = $('.wallet_used').text();
                if (wallet_used == '') {
                    wallet_used = 0;
                } else {
                    wallet_used = wallet_used.replace(',', '');
                }

                var delivery_charge = delivery_charge.toLocaleString(undefined, { maximumFractionDigits: 5 });
                delivery_charge = delivery_charge.replace(',', '');
                var final_total = parseFloat(sub_total) + parseFloat(delivery_charge) - parseFloat(wallet_used) - parseFloat(promocode_amount);
                final_total = final_total.toLocaleString(undefined, { maximumFractionDigits: 2 });
                $('#final_total').html(final_total);
                var final_total = final_total.replace(',', '');
                $('#amount').val(final_total);
                if (final_total != 0) {
                    $('#cod').prop('required', true);
                    $('#paypal').prop('required', true);
                    $('#razorpay').prop('required', true);
                    $('#paystack').prop('required', true);
                    $('#payumoney').prop('required', true);
                    $('#flutterwave').prop('required', true);
                    $('#stripe').prop('required', true);
                    $('#paytm').prop('required', true);
                    $('#bank_transfer').prop('required', true);
                    $('.payment-methods').show();
                }
            }
        });
        // $.ajax({
        //     type: 'POST',
        //     data: {
        //         [csrfName]: csrfHash,
        //         'address_id': address_id,
        //     },
        //     url: base_url + 'cart/check-product-availability',
        //     dataType: 'json',
        //     success: function(result) {
        //         csrfName = result.csrfName;
        //         csrfHash = result.csrfHash;

        //         var className = (result.error == true) ? "danger" : "success";
        //         $('#deliverable_status').html("<b class='text-" + className + "'>" + result.message + "</b>");
        //         result.data.forEach(product => {
        //             if (product.is_deliverable == false) {
        //                 $('#p_' + product.product_id).html("<b class='text-danger'>Not deliverable</b>");
        //             }
        //         });
        //     }
        // });

    }); */
    
});

$('#datepicker').attr({
    'placeholder': 'Preferred Delivery Date',
    'autocomplete': 'off'
});
$('#datepicker').on('cancel.daterangepicker', function (ev, picker) {
    $(this).val('');
    $('#start_date').val('');
});
$('#datepicker').on('apply.daterangepicker', function (ev, picker) {
    // console.log(picker.locale);
    // var drp = $('#datepicker').data('daterangepicker');
    var drp = picker;
    console.log(drp);
    var current_time = moment().format("HH:mm");
    if (moment(drp.startDate).isSame(moment(), 'd')) {
        $('.time-slot-inputs').each(function (i, e) {
            if ($(this).data('last_order_time') < current_time) {
                $(this).prop('checked', false).attr('required', false);
                $(this).parent().hide();
            } else {
                $(this).attr('required', true);
                $(this).parent().show();
            }
        });
    } else {
        $('.time-slot-inputs').each(function (i, e) {
            $(this).attr('required', true);
            $(this).parent().show();
        });
    }
    $('#start_date').val(drp.startDate.format('YYYY-MM-DD'));
    $('#delivery_date').val(drp.startDate.format('YYYY-MM-DD'));
    $(this).val(picker.startDate.format('MM/DD/YYYY'));
});
var mindate = '',
    maxdate = '';
if ($('#delivery_starts_from').val() != "") {
    mindate = moment().add(($('#delivery_starts_from').val() - 1), 'days');
} else {
    mindate = null;
}

if ($('#delivery_ends_in').val() != "") {
    maxdate = moment(mindate).add(($('#delivery_ends_in').val() - 1), 'days');
} else {
    maxdate = null;
}
$('#datepicker').daterangepicker({
    showDropdowns: false,
    alwaysShowCalendars: true,
    autoUpdateInput: false,
    singleDatePicker: true,
    minDate: mindate,
    maxDate: maxdate,
    locale: {
        "format": "DD/MM/YYYY",
        "separator": " - ",
        "cancelLabel": 'Clear',
        'label': 'Preferred Delivery Date'
    }
});

/* --->>> Commented Out Since Being Overridden and Handled in Cretzo's 'checkout.js' file <<<--- */
/* $(document).ready(function () {
    var address_id = $('#address_id').val();
    var sub_total = $('#sub_total').val();
    var total = $('#temp_total').val();
    $.ajax({
        type: 'POST',
        data: {
            [csrfName]: csrfHash,
            'address_id': address_id,
            'total': total,
        },
        url: base_url + 'cart/get-delivery-charge',
        dataType: 'json',
        success: function (result) {
            console.log(result);
            csrfName = result.csrfName;
            csrfHash = result.csrfHash;
            var className = result.error == true ? 'danger' : 'success'
            var is_time_slots_enabled = 0
            $('#deliverable_status').html(
                "<b class='text-" + className + "'>" + result.message + '</b>'
            )

            if(result.error){
                console.log("Result has errors !");
                return;
            }

            result.availability_data.forEach(product => {

                if (product.is_deliverable == false) {
                    $('#p_' + product.product_id).html(
                        "<b class='text-danger'> " +
                        (product.message ?? 'Not deliverable') +
                        '</b>'
                    )
                } else {
                    $('#p_' + product.product_id).html('')
                }
                if (product.delivery_by == 'standard_shipping') {
                    is_time_slots_enabled = 0
                    $('#is_time_slots_enabled').val(is_time_slots_enabled)
                }
            })
            $('.shipping_method').html(result.shipping_method)
            $('.delivery-charge').html(result.delivery_charge)
            $('.delivery_charge_with_cod').html(result.delivery_charge_with_cod)
            $('.delivery_charge_with_cod').val(result.delivery_charge_with_cod)
            $('.delivery_charge_without_cod').html(result.delivery_charge_without_cod)
            $('.delivery_charge_without_cod').val(result.delivery_charge_without_cod)
            $('.estimate_date').html(result.estimate_date)
            var shipping_method = result.shipping_method
            var delivery_charge = result.delivery_charge_with_cod
            var delivery_charge_with_cod = result.delivery_charge_with_cod
            var delivery_charge_without_cod = result.delivery_charge_without_cod
            result.availability_data.forEach(product => {
                if (product.delivery_by == 'standard_shipping') {
                    $('.date-time-label').addClass('d-none')
                    $('.date-time-picker').addClass('d-none')
                    $('.time-slot').addClass('d-none')
                } else {
                    $('.date-time-label').removeClass('d-none')
                    $('.date-time-picker').removeClass('d-none')
                    $('.time-slot').removeClass('d-none')
                }
            })
            if (shipping_method == 1) {
                var final_total =
                    parseFloat(sub_total) + parseFloat(delivery_charge_with_cod)
            } else {
                var final_total = parseFloat(sub_total) + parseFloat(delivery_charge)
            }

            $("#amount").val(final_total);
            final_total = final_total.toLocaleString(undefined, { maximumFractionDigits: 2 });
            $('#final_total').html(final_total);

        }

    })
}); */

$("input[name='payment_method']").on('change', function (e) {
    e.preventDefault();
    var payment_method = $(this).val();
    if (payment_method == "Direct Bank Transfer") {
        $('#account_data').show();
        $('#bank_transfer_slide').slideDown();
    } else {
        $('#account_data').hide();
        $('#bank_transfer_slide').slideUp();
    }
});

function printDiv(divName) {
    var printContents = document.getElementById(divName).innerHTML;
    var originalContents = document.body.innerHTML;
    var cls = document.getElementsByClassName('print-section');
    document.body.innerHTML = printContents;
    Array.prototype.forEach.call(cls, (item) => item.setAttribute("id", 'section-to-print'));
    setTimeout(function () { window.print(); }, 600);
    setTimeout(() => { document.body.innerHTML = originalContents; }, 1000);
}