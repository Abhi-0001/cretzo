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
    var total_mrp = 0;

    /* Recompute the bill from each cart line's rendered price.

       A discounted item renders BOTH a ".discounted-price" (its selling price)
       and an ".actual-price" (its MRP). A full-price item renders ONLY an
       ".actual-price" - which is still its selling price. The old version summed
       the subtotal from ".discounted-price" alone, so full-price items were
       silently dropped from both the subtotal and the item count, corrupting
       the bill as soon as their quantity changed. Sum per line instead:
         selling = discounted-price if present, otherwise actual-price
         mrp     = actual-price (always present)                              */
    $(".cart-item.cart-product .total-price").each(function () {
        var $tp = $(this);
        var $disc = $tp.find(".discounted-price.product-line-price");
        var $act = $tp.find(".actual-price.product-line-price");

        var sellingText = $disc.length ? $disc.first().text() : $act.first().text();
        var selling = parseFloat(sellingText.replace(/[^\d\.]/g, "")) || 0;
        var mrp = $act.length ? (parseFloat($act.first().text().replace(/[^\d\.]/g, "")) || selling) : selling;

        subtotal += selling;
        total_mrp += mrp;
        cart_count++;
    });

    subtotal = Math.round(subtotal);
    total_mrp = Math.round(total_mrp);

    // var shipping = parseFloat($("#final_shipping_fee").text().replace(/[^\d\.]/g, ""));
    var shipping = 0; // ! we have decided to hide shipping fee until address is selected on checkout page

    var total = subtotal + shipping - parseFloat(getAppliedPromocodeAmount());

    /* Update Bill Text For Promocode */
    updatePromoCodeInfoOnBill();

    $("#final_discount_mrp").text('- ₹'+moneyFormatIndia(total_mrp - subtotal));
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
    // Keep checkout disabled when the cart is empty OR when any item is out of stock
    // (the server marks the button with data-out-of-stock when a line is unavailable).
    var hasOutOfStock = $('#place-order-btn').data('out-of-stock') == 1;
    $('#place-order-btn').attr("disabled", (cart_count <= 0) || hasOutOfStock);

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

    function formatPromoExpiry(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d.getTime())) return dateStr;
        var day = d.getDate();
        var suffix = (day % 10 == 1 && day != 11) ? 'st'
                   : (day % 10 == 2 && day != 12) ? 'nd'
                   : (day % 10 == 3 && day != 13) ? 'rd' : 'th';
        var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        return day + suffix + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    // Rendered inside the modal itself so it can never be hidden behind the
    // backdrop or the modal's own close animation, unlike the global Toast.
    function showModalPromoToast(type, message) {
        $('#promo-modal-toast')
            .removeClass('d-none is-success is-error')
            .addClass(type === 'success' ? 'is-success' : 'is-error')
            .text(message);
    }
    function hideModalPromoToast() {
        $('#promo-modal-toast').addClass('d-none').text('');
    }
    function updateModalMaxSavings(amount) {
        $('#promo_modal_max_savings').text('₹' + moneyFormatIndia(amount || 0));
    }

    // Toggles the "Apply Coupons" widget (above Delivery Estimates) between its
    // default state and the "1 Coupon Applied / You saved additional ₹X" state.
    function updateCouponWidget(applied, amount) {
        $('#coupon-widget-default').toggleClass('d-none', applied);
        $('#coupon-widget-applied').toggleClass('d-none', !applied);

        var $subtext = $('#coupon-widget-subtext');
        if (applied) {
            $subtext.addClass('is-applied').text('You saved additional ₹' + moneyFormatIndia(amount || 0));
        } else {
            $subtext.removeClass('is-applied').text('Show Your Support For Our Artisans By Purchasing Their Handcrafted Artworks.');
        }
    }

    // Shared by the outer "Redeem" input and the coupon modal's Check/Apply controls.
    function submitPromoCode(code, callback) {
        var formdata = new FormData();
        formdata.append(csrfName, csrfHash);
        formdata.append('promo_code', code);
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
                csrfName = data.csrfName;
                csrfHash = data.csrfHash;

                if (data.error == false) {
                    var final_discount = parseFloat(data.data[0].final_discount);

                    $('.promocode_input').attr('disabled', false).val(code);
                    $('#promocode_div').removeClass('d-none');
                    $('.clear_promo_btn').removeClass('d-none');
                    $('.redeem_btn').hide();

                    $("#promo_set").val(1);
                    $('#promo_code_amount').val(final_discount);
                    $('.promocode_input').attr('disabled', true);

                    updateCouponWidget(true, final_discount);
                    refreshBill();
                } else {
                    $("#promo_set").val(0);
                    $('#promo_code_amount').val(0);
                    $('.promocode_input').attr('disabled', false);

                    updateCouponWidget(false);
                    refreshBill();
                }

                if (typeof callback === 'function') callback(data);
            },
            error: function () {
                if (typeof callback === 'function') {
                    callback({ error: true, message: 'Something went wrong. Please try again.' });
                }
            }
        });
    }

    // redeem button (below the price detail table)
    $(".redeem_btn").on('click', function (event) {
        event.preventDefault();
        var code = $('.promocode_input').val();
        submitPromoCode(code, function (data) {
            Toast.fire({
                icon: data.error ? 'error' : 'success',
                title: data.error ? data.message : 'Coupon applied successfully!'
            });
            if (data.error) {
                $('.promocode_input').val('');
            }
        });
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

        updateCouponWidget(false);
        refreshBill();
    })

    /* --- "Apply Coupon" modal --- */

    // picking a coupon only selects it - applying happens via the Apply button,
    // so the modal stays open long enough for the inline toast to be seen.
    $(document).on('change', '.promo-card-radio', function () {
        hideModalPromoToast();
        var $card = $(this).closest('.promo-card');
        $('#promo_modal_input').val($(this).val());
        updateModalMaxSavings($card.data('max'));
        $('#promo_modal_apply_btn').prop('disabled', false);
    });
    $(document).on('click', '.promo-card', function (e) {
        if (e.target.tagName !== 'INPUT') {
            $(this).find('.promo-card-radio').prop('checked', true).trigger('change');
        }
    });

    function applyFromModal(code) {
        if (!code) {
            showModalPromoToast('error', 'Please enter a coupon code');
            return;
        }
        var $checkBtn = $('#promo_modal_check_btn');
        var $applyBtn = $('#promo_modal_apply_btn');
        $checkBtn.prop('disabled', true);
        $applyBtn.prop('disabled', true);

        submitPromoCode(code, function (data) {
            $checkBtn.prop('disabled', false);
            $applyBtn.prop('disabled', false);

            if (data.error == false) {
                showModalPromoToast('success', 'Coupon applied successfully!');
                updateModalMaxSavings(data.data[0].final_discount);
                setTimeout(function () {
                    $('#promo-code-modal .btn-close').trigger('click');
                    hideModalPromoToast();
                }, 900);
            } else {
                showModalPromoToast('error', data.message);
            }
        });
    }

    $('#promo_modal_check_btn').on('click', function (event) {
        event.preventDefault();
        applyFromModal($('#promo_modal_input').val());
    });

    $('#promo_modal_apply_btn').on('click', function (event) {
        event.preventDefault();
        var $checked = $('.promo-card-radio:checked');
        var code = $checked.length ? $checked.val() : $('#promo_modal_input').val();
        applyFromModal(code);
    });

    document.getElementById("promo-code-modal").addEventListener("show.bs.modal", () => {
        hideModalPromoToast();
        $('#promo_modal_input').val('');
        $('#promo_modal_apply_btn').prop('disabled', true);
        updateModalMaxSavings(0);

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
                        var discount = parseFloat(e.discount);
                        var maxDiscount = parseFloat(e.max_discount_amt);
                        var offText = (e.discount_type == 'percentage') ? (discount + '% off .') : ('Flat ₹' + moneyFormatIndia(discount) + ' off .');

                        html += '<li class="promo-card" data-max="' + (maxDiscount || 0) + '">' +
                            '<label class="promo-card-select">' +
                            '<input type="radio" name="promo_select" class="promo-card-radio" value="' + e.promo_code + '">' +
                            '<span class="promo-card-checkmark"></span>' +
                            '</label>' +
                            '<div class="promo-card-body">' +
                            '<div class="promo-card-code-chip">' + e.promo_code + '</div>' +
                            '<p class="promo-card-save">Save ₹' + moneyFormatIndia(maxDiscount || 0) + '</p>' +
                            '<p class="promo-card-off">' + offText + '</p>' +
                            '<p class="promo-card-expiry">Expires on: ' + formatPromoExpiry(e.end_date) + '</p>' +
                            '<p class="promo-card-desc">' + e.message + '</p>' +
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