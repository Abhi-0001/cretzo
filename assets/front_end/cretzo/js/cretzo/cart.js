/* Copy a promo code to the clipboard and show a confirmation toast.
   Uses the async Clipboard API when available, with a hidden-textarea
   fallback for older browsers / non-secure contexts. */
function copyPromoCode(code) {
    if (!code) {
        return;
    }
    code = String(code);

    function notifyCopied() {
        if (typeof Toast !== 'undefined' && Toast.fire) {
            Toast.fire({ icon: 'success', title: 'Code "' + code + '" copied!' });
        }
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code).then(notifyCopied).catch(function () {
            fallbackCopyText(code);
            notifyCopied();
        });
    } else {
        fallbackCopyText(code);
        notifyCopied();
    }
}

function fallbackCopyText(text) {
    var $temp = $('<textarea>').css({ position: 'fixed', top: '-9999px', opacity: 0 }).val(text).appendTo('body');
    $temp[0].select();
    try {
        document.execCommand('copy');
    } catch (err) { /* clipboard not available */ }
    $temp.remove();
}

function updateCartDetails(){
    var cart_count = 0;

    var subtotal = 0;
    $(".total-price  > .discounted-price.product-line-price").each(function (t) {
        subtotal = Math.round(parseFloat(subtotal) + parseFloat($(this).text().replace(/[^\d\.]/g, "")));
        cart_count++;
    });
    
    var total_mrp = 0;
    $(".total-price  > .actual-price.product-line-price").each(function (t) {
        total_mrp = Math.round(parseFloat(total_mrp) + parseFloat($(this).text().replace(/[^\d\.]/g, "")));
    });

    // var shipping = parseFloat($("#final_shipping_fee").text().replace(/[^\d\.]/g, ""));
    var shipping = 0; // ! we have decided to hide shipping fee until address is selected on checkout page

    var total = subtotal + shipping - parseFloat(getAppliedPromocodeAmount());

    /* Update Bill Text For Promocode */
    updatePromoCodeInfoOnBill();

    $("#final_discount_mrp").text('₹'+moneyFormatIndia(total_mrp - subtotal));
    $("#final_total_mrp").text('₹'+moneyFormatIndia(total_mrp));
    $("#final_subtotal").text('₹'+moneyFormatIndia(subtotal));
    $("#final_total").text('₹'+moneyFormatIndia(total));

    // update cart count
    $(".cart-count").text(cart_count);
    
    if($('#select-all-checkbox').prop('checked')){
        $('.cart-count-checked').text(cart_count);
    }

    // set state of checkout button
    if(cart_count <= 0){
        $(".checkout").addClass('disabled');
        $('#select-all-checkbox').prop('checked', false);
        $('#select-all-checkbox').attr("disabled", true);
    }
    else{
        $(".checkout").removeClass('disabled');
        $('#select-all-checkbox').removeAttr("disabled");
    }
}

function refreshBill(){
    updateCartDetails();
}

$(document).ready(function() {

    var cart_count = $('#input_cart_count').val();
    $('#place-order-btn').attr("disabled", cart_count <= 0);

    $(document).on("click", "#place-order-btn", function (e) {
        var url = $(this).data('url');
        if(getAppliedPromocode()){
            url = url + `?promo=${getAppliedPromocode()}`;
        }
        window.location.href = url;
    })
    
    $('#select-all-checkbox').change(function() {
        if(this.checked) {
            // $(this).prop("checked", returnVal);
            $('.selected-items-action-btn').removeClass('disabled');
            $('.cart-count-checked').text($('.cart-count').first().text());
        }
        else{
            $('.selected-items-action-btn').addClass('disabled');
            $('.cart-count-checked').text('0');
        }
    });

    $("#wishlist-all").on("click", function (e) {
        e.preventDefault();
    
        // Get all product IDs you want to add to favorites
        var productIds = [];
        $(".cart-item.cart-product").each(function () {
            productIds.push($(this).data("product-id"));
        });
    
        if (productIds.length === 0) {
            Toast.fire({
                icon: "error",
                title: "Please select at least one product!"
            });
            return;
        }
    
        var t = new FormData();
        t.append(csrfName, csrfHash);
        for (var i = 0; i < productIds.length; i++) {
            t.append("product_ids[]", productIds[i]);
        }
    
        $.ajax({
            type: "POST",
            url: base_url + "my-account/add_to_favorites",
            data: t,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (e) {
                csrfName = e.csrfName;
                csrfHash = e.csrfHash;
                if (e.error === true) {
                    Toast.fire({
                        icon: "error",
                        title: e.message
                    });
                } else {
                    Toast.fire({
                        icon: "success",
                        title: "Favorites updated!"
                    });
                    // Optionally update the UI to reflect changes
                }
            }
        });
    });
    
});

/* Promo Code Functions */
$(document).ready(function() {
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

    $(document).on('click', '#redeem_promocode', function (event) {
        event.preventDefault();
        var promo_code = $(this).data('value')
        // $('#promocode_input').val(promo_code);
        $('.promocode_input').val(promo_code);
        copyPromoCode(promo_code);
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
                        html += '<li class="promo-card">' +
                            '<div class="promo-card-top">' +
                            '<div class="promo-card-img">' +
                            '<img src="' + e.image + '" alt="' + e.promo_code + '" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';"/>' +
                            '<i class="fa fa-gift promo-card-img-fallback" style="display:none;"></i>' +
                            '</div>' +
                            '<div class="promo-card-right">' +
                            '<div class="promo-card-code copy-promo-code" title="Copy promocode" id="redeem_promocode" data-value="' + e.promo_code + '">' +
                            '<span class="promo-card-code-text">' + e.promo_code + '</span>' +
                            '<i class="fa fa-copy promo-card-copy"></i>' +
                            '</div>' +
                            '<p class="promo-card-desc">' + e.message + '</p>' +
                            '</div>' +
                            '</div>' +
                            '</li>';
                    });
                } else {
                    html += '<li class="promo-empty">Oops... No offers available</li>';
                }
                $('#promocode-list').html(html);
            }
        })
    });
});

/* Get the applied promocode */
function getAppliedPromocode(){
    var promo_set = $('#promo_set').val()
    if (promo_set == 1) {
        return $('.promocode_input').val();
    }

    return false;
}
/* Get the applied promocode amount */
function getAppliedPromocodeAmount(){
    var promo_set = $('#promo_set').val()
    var promocode_amount = 0;
    if (promo_set == 1) {
        promocode_amount = $('#promo_code_amount').val();
    }
    
    return promocode_amount;
}

function updatePromoCodeInfoOnBill(){
    var promocode_amount = getAppliedPromocodeAmount();

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