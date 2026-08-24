var bill_sub_total = 0;
var bill_total = 0;
var bill_final_payable = 0; // The final amount payable.
var isCOD = false;
var current_wallet_balance = 0;
var used_wallet_balance = 0;

var selected_payment_method;
var complete_wallet_payment;

function setupActionButton(){
    document.getElementsByClassName('go-to-payment-btn')[0].onclick = function(){
        (document.getElementsByClassName('cart-container address')[0]).classList.add('d-none');
        (document.getElementsByClassName('cart-container payment')[0]).classList.remove('d-none');
        return false;
    }

    $('.go-to-payment-btn').attr("disabled", true);
    $('.place-order-btn').attr("disabled", true);
}

function setupPaymentCheckbox(){

    selected_payment_method = $("input[name='payment_method']:checked")[0].id;

    $("input[name='payment_method']").change(function() {

        // Store selected payment method except for 'wallet'
        if(this.id != 'wallet'){
            selected_payment_method = this.id;
        }

        switch(this.id){
            case 'cod':
                isCOD = true;
                $('.final-shipping-title-cod-tag').removeClass('d-none');
                break;
            default:
                isCOD = false;
                $('.final-shipping-title-cod-tag').addClass('d-none');
                break;
        }

        refreshBill();
    });
}

function setupWalletBalance(){
    $('#wallet_balance').on('click', function (e) {

        complete_wallet_payment = false; // mark false initially
        $('.payment-methods').show(); // show payment methods initially

        if ($(this).is(':checked')) {

            $("#wallet_used").val(1);

            $('.wallet-section').removeClass('d-none');            

            wallet_balance = parseFloat(current_wallet_balance);

            var bill_total_without_cod = getBillTotal(false);

            if (bill_total_without_cod - wallet_balance <= 0) {  // total bill can be paid by the wallet alone

                var available_balance = wallet_balance - bill_total_without_cod;
                available_balance = parseFloat(available_balance);

                used_wallet_balance = bill_total_without_cod;

                $(".wallet_used").html(bill_total_without_cod.toLocaleString(undefined, {
                    maximumFractionDigits: 2
                }));

                $('#available_balance').html(available_balance.toLocaleString(undefined, {
                    maximumFractionDigits: 2
                }));
                
                $('#cod').prop('required', false);
                $('#razorpay').prop('required', false);
                // $('#bank_transfer').prop('required', false);

                $('.payment-methods').hide();

                complete_wallet_payment = true;

            } else { // total bill might be partially payable by the wallet

                $(".wallet_used").html(wallet_balance);
                $('#available_balance').html('0.00');

                used_wallet_balance = wallet_balance;

                $('#cod').prop('required', true);
                $('#razorpay').prop('required', true);
                // $('#bank_transfer').prop('required', true);
            }
        }
        else{
            
            $("#wallet_used").val(0);

            $('.wallet-section').addClass('d-none');

            $('#available_balance').html(wallet_balance.toLocaleString(undefined, {
                maximumFractionDigits: 2
            }));

            used_wallet_balance = 0;
        }

        if(complete_wallet_payment){
            $('#wallet').prop("checked", true).trigger('change');
        }
        else{
            $(`#${selected_payment_method}`).prop("checked", true).trigger('change');
        }

        // refreshBill(); // Commented out because already being called when payment method is triggered.
    });
}

function setupSelectAddress(){
    $('.cart-left-two-left .address-container').click(function() {
        $('.cart-left-two-left .address-container').removeClass('selected-address');
        $(this).addClass('selected-address');

        $('.address-action-btn-select').html("SELECT");
        $('.address-action-btn-select').css('display', 'block');
        $(this).find('.address-action-btn-select').html("SELECTED");
        $(this).find('.address-action-btn-select').css('display', 'none');

        $('.address-action-btn-select').attr("disabled", false);
        $(this).find('.address-action-btn-select').attr("disabled", true);

        selectAddress($(this).data("row"));
    });

    /* Edit the address in the default modal that is available (requires proper binding and bug fixes, which we can do later) */
    /* $('.address-action-btn-edit').on('click', function (event) {
        updateEditAddressForm($(this).data("row"));
        $('#address-modal').modal('show');
    }); */
}

$(document).ready(function() {
    /* We are storing total and sub_total values in a variable for security reasons */
    bill_sub_total = $('#input_sub_total').val();
    bill_sub_total = bill_sub_total.replace(',', '');

    /* bill_total = $('#input_total').val();
    bill_total = bill_total.replace(',', ''); */
    bill_total = bill_sub_total; // Assigning to subtotal, since we aren't adding the delivery charge fetched by default

    /* Also store the wallet balance in a variable */
    current_wallet_balance = $('#current_wallet_balance').val();
    current_wallet_balance = current_wallet_balance.replace(",", "");

    initialPageSetup();
});

/* Function called when page is loaded, to setup the page (enable/disable button, hide/show relevant texts, etc) */
function initialPageSetup(){

    setupActionButton();
    setupPaymentCheckbox();
    setupWalletBalance();
    setupSelectAddress();
    setupClickEvents();

    checkForPromoInQuery();
    
    $('p:has(> .estimate_date)').hide();

    /* Select the address that is marked as selected (the address set as 'default' is marked selected by default) */
    selectAddress($('.selected-address').data("row"));
}

/* Function called when an address is selected by the customer and the address details are fetched by an AJAX call (mainly to update the form for errors/success) */
function onAddressSelectedAndDetailsFetched(error, message){
    $('.go-to-payment-btn').attr("disabled", error);
    $('.place-order-btn').attr("disabled", error);
    
    if(error){
        $('.address-error-msg').show();
        $('.address-error-msg').text(message);

        $('p:has(> .estimate_date)').hide();
    }
    else{
        $('.address-error-msg').hide();
        $('.address-error-msg').text("");

        $('p:has(> .estimate_date)').show();
    }
}

function updateProductDeliveryStatus(product_id, is_deliverable, message){
    if(is_deliverable){
        $(`#p_${product_id} .estimate-list-item-text`).text(message);
        $(`#p_${product_id} .estimate-list-item-text`).removeClass("error-text");
        $(`#p_${product_id}`).removeClass("error-box");
    }
    else{
        $(`#p_${product_id} .estimate-list-item-text`).text(message ?? "Not deliverable at selected address.");
        $(`#p_${product_id} .estimate-list-item-text`).addClass("error-text");
        $(`#p_${product_id}`).addClass("error-box");
    }
}

function selectAddress(address) {
    $('#address-name-type').html(address.name + ' - ' + address.type);
    $('#address-full').html(address.area + ' , ' + address.city);
    $('#address-country').html(address.state + ' , ' + address.country + ' - ' + address.pincode);
    $('#address-mobile').html(address.mobile);
    $('#input_address_id').val(address.id);
    $('#mobile').val(address.mobile);

    // $('.address_modal').modal('hide');
    // $('.address-modal').iziModal('close');
    
    var address_id = $('#input_address_id').val();

    $('.go-to-payment-btn').attr("disabled", true);
    var t_01 = $('.go-to-payment-btn').html();
    $('.go-to-payment-btn').html('Please wait...');

    $.ajax({
        type: 'POST',
        data: {
            [csrfName]: csrfHash,
            'address_id': address_id,
            'total': bill_sub_total,
        },
        url: base_url + 'cart/get-delivery-charge',
        dataType: 'json',
        success: function (result) {

            $('.go-to-payment-btn').html(t_01);
            onAddressSelectedAndDetailsFetched(result.error, result.message);

            console.log(result);

            csrfName = result.csrfName;
            csrfHash = result.csrfHash;

            // var is_time_slots_enabled = 0
            var className = result.error == true ? 'danger' : 'success'

            /* $('#checkout_form > .row').unblock()
            $('#deliverable_status').html(
                "<b class='text-" + className + "'>" + result.message + '</b>'
            ) */

            result.availability_data.forEach(product => {

                updateProductDeliveryStatus(product.product_id, product.is_deliverable, product.message);

                /* if (product.is_deliverable == false) {
                    $('#p_' + product.product_id).html(
                        "<b class='text-danger'> " +
                        (product.message ?? 'Not deliverable') +
                        '</b>'
                    )
                } else {
                    $('#p_' + product.product_id).html('')
                } */

                /* if (product.delivery_by == 'standard_shipping') {
                    is_time_slots_enabled = 0
                    $('#is_time_slots_enabled').val(is_time_slots_enabled)
                } */
            })

            $('.estimate_date').html(result.estimate_date);

            /* $('.shipping_method').html(result.shipping_method)
            $('.delivery-charge').html(result.delivery_charge_with_cod)
            $('.delivery-charge').val(result.delivery_charge_with_cod)
            $('.delivery_charge_with_cod').html(result.delivery_charge_with_cod)
            $('.delivery_charge_with_cod').val(result.delivery_charge_with_cod)
            $('.delivery_charge_without_cod').html(result.delivery_charge_without_cod)
            $('.delivery_charge_without_cod').val(result.delivery_charge_without_cod) */



            $('#delivery_charge_with_cod').val(Math.round(result.delivery_charge_with_cod));
            $('#delivery_charge_without_cod').val(Math.round(result.delivery_charge_without_cod));

            // $('.final-shipping').text('₹'+moneyFormatIndia(result.delivery_charge_without_cod));

            refreshBill();

            
            /* var shipping_method = result.shipping_method
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
            }) */
           
            /* var final_total = parseFloat(sub_total) + parseFloat(delivery_charge);
            var wallet_used = $('.wallet_used').text();
            if (wallet_used == '') {
                wallet_used = 0;
            } else {
                wallet_used = wallet_used.replace(',', '');
            } */

            /* var delivery_charge = delivery_charge.toLocaleString(undefined, { maximumFractionDigits: 5 });
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
            } */
        },
        error: function () {
            $('.go-to-payment-btn').html(t_01).removeAttr('disabled');
            onAddressSelectedAndDetailsFetched(true, 'Could not check delivery availability. Please try again.');
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
}

/* Get the delivery charge that is applicable */
function getDeliveryCharge(b_isCOD){
    return b_isCOD ? $('#delivery_charge_with_cod').val() : $('#delivery_charge_without_cod').val();
}

/* Get the applied promocode */
function getAppliedPromocode(){
    var promo_set = $('#promo_set').val()
    var promocode_amount = 0;
    if (promo_set == 1) {
        promocode_amount = $('#promo_code_amount').val();
    }
    
    return promocode_amount;
}

/* Get the bill amount with the 'subtotal', 'delivery charge' and promocode (no wallet accounted for in this function) */
function getBillTotal(b_isCOD){
    return parseFloat(bill_sub_total) + parseFloat(getDeliveryCharge(b_isCOD)) - parseFloat(getAppliedPromocode());
}

function refreshBill(){
    

    // var delivery_charge = delivery_charge.toLocaleString(undefined, { maximumFractionDigits: 5 });
    // delivery_charge = delivery_charge.replace(',', '');

    // The final total (without applying wallet)
    var final_total = getBillTotal(isCOD);
    bill_total = final_total; // We will not subtract 'used wallet' amount from bill_total.

    // final_total with wallet applied
    bill_final_payable = final_total - parseFloat(used_wallet_balance);

    /* Update the values on the displayed bill */

    /* Update Bill Text For Delivery Charge */
    $('.final-shipping').text('₹'+moneyFormatIndia(getDeliveryCharge(isCOD)));

    /* Update Bill Text For Promocode */
    updatePromoCodeInfoOnBill();
    /* $('.final-promocode').text('₹'+moneyFormatIndia(getAppliedPromocode()));
    getAppliedPromocode() > 0 ? $('.final-promocode').removeClass('d-none') : $('.final-promocode').addClass('d-none'); // hide/show amount depending if promocode applied */

    /* Update Bill Text For Total */
    $('.final-total').html(bill_final_payable.toLocaleString(undefined, { maximumFractionDigits: 2 }));
    
    // $('#amount').val(bill_final_payable);
    
    /* If payable amount > 0, show payment methods */
    if (bill_final_payable > 0) {
        $('#cod').prop('required', true);
        $('#razorpay').prop('required', true);
        // $('#bank_transfer').prop('required', true);
        $('.payment-methods').show();
    }
    
}

function place_order() {
    let myForm = document.getElementById('checkout_form');
    var formdata = new FormData(myForm);
    formdata.append(csrfName, csrfHash);
    formdata.append('promo_code', $('.promocode_input').val());
    var latitude = sessionStorage.getItem("latitude") === null ? '' : sessionStorage.getItem("latitude");
    var longitude = sessionStorage.getItem("longitude") === null ? '' : sessionStorage.getItem("longitude");
    formdata.append('latitude', latitude);
    formdata.append('longitude', longitude);

    /* cretzo specific */
    formdata.append('address_id', $('#input_address_id').val());
    formdata.append('payment_method', $("input[name=payment_method]:checked").val());

    return $.ajax({
        type: 'POST',
        data: formdata,
        url: base_url + 'cart/place-order',
        dataType: 'json',
        cache: false,
        processData: false,
        contentType: false,
        beforeSend: function () {
            $('#place-order-btn').attr('disabled', true).html('Please Wait...');
        },
        success: function (data) {
            csrfName = data.csrfName;
            csrfHash = data.csrfHash;
            $('#place-order-btn').attr('disabled', false).html('Place Order');
            if (data.error == false) {
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        },
        error: function (jqXHR) {
            $('#place-order-btn').attr('disabled', false).html('Place Order');
            Toast.fire({
                icon: 'error',
                title: 'Something went wrong while placing your order. Please try again.'
            });
            console.log('place-order error:', jqXHR.status, jqXHR.responseText);
        }
    })
}

/*
 * The success page has to know WHICH order it is confirming: Cash on Delivery, a wallet
 * payment and a Razorpay payment all land here, and the page's wording differs (a COD order
 * has not been paid for yet). Payment::success() falls back to the user's newest order when no
 * id is passed, but passing it is exact.
 */
function payment_success_url(result) {
    var order_id = (result && result.data && result.data.order_id) ? result.data.order_id : '';
    return base_url + 'payment/success' + (order_id ? '?order_id=' + encodeURIComponent(order_id) : '');
}

function razorpay_setup(key, amount, app_name, logo, razorpay_order_id, username, user_email, user_contact) {
    var options = {
        "key": key, // Enter the Key ID generated from the Dashboard
        "amount": (amount * 100), // Amount is in currency subunits. Default currency is INR. Hence, 50000 refers to 50000 paise
        "currency": "INR",
        "name": app_name,
        "description": "Product Purchase",
        "image": logo,
        "order_id": razorpay_order_id, //This is a sample Order ID. Pass the `id` obtained in the response of Step 1
        "handler": function (response) {
            $('#razorpay_payment_id').val(response.razorpay_payment_id);
            $('#razorpay_signature').val(response.razorpay_signature);
            place_order().done(function (result) {
                if (result.error == false) {
                    setTimeout(function () {
                        location.href = payment_success_url(result);
                    }, 3000);
                }
            });
        },
        "prefill": {
            "name": username,
            "email": user_email,
            "contact": user_contact
        },
        "notes": {
            "address": app_name + " Purchase"
        },
        "theme": {
            "color": $("#theme_color").css("color")
        },
        "escape": false,
        "modal": {
            "ondismiss": function () {
                // $('#place_order_btn').attr('disabled', false).html('Place Order');
            }
        }
    };
    var rzp = new Razorpay(options);
    return rzp;
}

function setupClickEvents(){

    // $("#checkout_form").on('submit', function (event) {
    $("#place-order-btn").on('click', function (event) {
        event.preventDefault();

        var address_id = $("#input_address_id").val();
        var product_type = $("#product_type").val();
        var documents = $('#documents').val();
        
        /* if ($('#wallet_balance').is(":checked")) {
            var wallet_used = 1;
        } else {
            var wallet_used = 0;
        } */
        var wallet_used = used_wallet_balance > 0;

        var promo_set = $('#promo_set').val();
        var promo_code = '';
        if (promo_set == 1) {
            promo_code = $('.promocode_input').val();
        }
        
        /* var final_total = $("#final_total").text();
        final_total = final_total.replace(',', ''); */

        var btn_html = $('#place-order-btn').html();
        $('#place-order-btn').attr('disabled', true).html('Please Wait...');

        /* if (($('#is_time_slots_enabled').val() == 1 && ($('input[name="delivery_time"]').is(':checked') == false || $('input[type=hidden][id="start_date"]').val() == "") && $('#product_type').val() != 'digital_product')) {
            Toast.fire({
                icon: 'error',
                title: "Please select Delivery Date & Time."
            });
            $('#place-order-btn').attr('disabled', false).html(btn_html);
            return false;
        } */

        if ((address_id == null || address_id == undefined || address_id == '') && $('#product_type').val() != 'digital_product') {
            Toast.fire({
                icon: 'error',
                title: "Please add/choose address."
            });
            $('#place-order-btn').attr('disabled', false).html(btn_html);
            return false;
        }
        if (documents === "") {
            $('#place-order-btn').attr('disabled', false).html(btn_html);
            return Toast.fire({
                icon: 'error',
                title: 'Please select an Document.'
            })
        }
        var payment_methods = $("input[name='payment_method']:checked").val();
        
        if (payment_methods == "Razorpay") {
            $.post(base_url + "cart/pre-payment-setup", {
                [csrfName]: csrfHash,
                'payment_method': 'Razorpay',
                'wallet_used': wallet_used,
                'address_id': address_id,
                'promo_code': promo_code,
                'documents': documents
            }, function (data) {
                csrfName = data.csrfName;
                csrfHash = data.csrfHash;
                if (data.error == false) {
                    $('#razorpay_order_id').val(data.order_id);
                    var key = $('#razorpay_key_id').val();
                    var app_name = $('#app_name').val();
                    var logo = $('#logo').val();
                    var razorpay_order_id = $('#razorpay_order_id').val();
                    var username = $('#username').val();
                    var user_email = $('#user_email').val();
                    var user_contact = $('#user_contact').val();
                    var rzp1 = razorpay_setup(key, bill_final_payable, app_name, logo, razorpay_order_id, username, user_email, user_contact);
                    rzp1.open();
                    rzp1.on('payment.failed', function (response) {
                        location.href = base_url + 'payment/cancel';
                    });
                    $('#place-order-btn').attr('disabled', false).html('Place Order');
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: data.message
                    });
                    $('#place-order-btn').attr('disabled', false).html('Place Order');

                }
            }, "json").fail(function (jqXHR) {
                // Razorpay order setup failed on the server — don't leave the button hung.
                $('#place-order-btn').attr('disabled', false).html('Place Order');
                Toast.fire({
                    icon: 'error',
                    title: 'Could not start the payment. Please try again.'
                });
                console.log('pre-payment-setup error:', jqXHR.status, jqXHR.responseText);
            });
        }
        else if (payment_methods == "COD" /* || payment_methods == "Direct Bank Transfer" */) {
            place_order().done(function (result) {
                if (result.error == false) {
                    setTimeout(function () {
                        location.href = payment_success_url(result);
                    }, 3000);
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: result.message
                    });
                }
            });
        }
        else if (complete_wallet_payment == 1 && bill_final_payable <= 0) {

            place_order().done(function (result) {
                if (result.error == false) {
                    location.href = payment_success_url(result);
                    // window.location.reload();
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: result.message
                    });
                }

            });

        }
        else {
            // No payment method matched (e.g. none selected, or partial-wallet without a
            // gateway). Reset the button so it can never stay stuck on "Please Wait...".
            $('#place-order-btn').attr('disabled', false).html(btn_html);
            Toast.fire({
                icon: 'error',
                title: 'Please select a payment method.'
            });
        }

    });

    // redeem button
    $(".redeem_btn").on('click', function (event) {
        event.preventDefault();
        var formdata = new FormData();
        formdata.append(csrfName, csrfHash);
        formdata.append('promo_code', $('.promocode_input').val());
        var address_id = $("#input_address_id").val();
        formdata.append('address_id', address_id);
        
        $.ajax({
            type: 'POST',
            data: formdata,
            url: base_url + 'cart/validate-promo-code',
            dataType: 'json',
            cache: false,
            processData: false,
            contentType: false,
            success: function (data) {
                console.log(data);
                csrfName = data.csrfName;
                csrfHash = data.csrfHash;
                if (data.error == false) {
                    /* Toast.fire({
                        icon: 'success',
                        title: data.message
                    }); */

                    var final_discount = parseFloat(data.data[0].final_discount);

                    $('#promocode_div').removeClass('d-none');
                    
                    $('.clear_promo_btn').removeClass('d-none');
                    $('.redeem_btn').hide();
                    
                    $("#promo_set").val(1);
                    $('#promo_code_amount').val(final_discount);

                    $('.promocode_input').attr('disabled', true);

                    refreshBill();
                } else {
                    /* Toast.fire({
                        icon: 'error',
                        title: data.message
                    }); */
                    $("#promo_set").val(0);
                    $('.promocode_input').val('');

                    $('#promo_code_amount').val(0);

                    $('.promocode_input').attr('disabled', false);

                    refreshBill();
                }
            }
        });

        refreshBill();
    });

    // clear promo code 
    $('.clear_promo_btn').on('click', function (event) {
        event.preventDefault();
        $('#promocode_div').addClass('d-none');
        
        $('.clear_promo_btn').addClass('d-none')
        $('.redeem_btn').show()
        $('.promocode_input').val('')
        $('#promo_set').val(0)

        $('#promo_code_amount').val(0);

        $('.promocode_input').attr('disabled', false);

        refreshBill();
    })
    /* Instantiating iziModal */

}

function updatePromoCodeInfoOnBill(){
    var promocode_amount = getAppliedPromocode();

    if(promocode_amount > 0){
        $('.see-offers-btn').addClass('d-none');
        $('.final-promocode').removeClass('d-none');
        $('.final-promocode-amount').removeClass('d-none');

        $('.final-promocode-amount').text('- ₹'+moneyFormatIndia(promocode_amount));
        $('.final-promocode').text('(' + $('.promocode_input').val() + ')');
        // $('.final-promocode-amount').text(final_discount.toLocaleString(undefined, { maximumFractionDigits: 2 }));
    }
    else{
        $('.see-offers-btn').removeClass('d-none');
        $('.final-promocode').addClass('d-none');
        $('.final-promocode-amount').addClass('d-none');

        $('.final-promocode-amount').text(0);
        $('.final-promocode').text();
    }
}

function checkForPromoInQuery(){
    const urlParams = new URLSearchParams(window.location.search);
    const promoParam = urlParams.get("promo");

    if (promoParam) {
        $('.promocode_input').val(promoParam);
        $(".redeem_btn").first().trigger('click');
    }
}

/* function updateEditAddressForm(row){
    console.log(row);
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
        console.log("in if");
        // alert("here2");
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
        console.log("in else");
        $("#edit_city").val(row.city_id).trigger('change', [row.pincode]);
    }
    if(row.type !="")
    {
        $('input[type=radio][value=' + row.type.toLowerCase() + ']').attr('checked', true);
    }
} */