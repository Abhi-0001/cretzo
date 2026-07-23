$(document).ready(function() {
    setupReadMoreLinks();
});

function moneyFormatIndia(num) {
    // Coerce to a finite number; fall back to 0 for empty/invalid input.
    var n = parseFloat(num);
    if (isNaN(n)) n = 0;

    var isNegative = n < 0;
    n = Math.abs(n);

    // Round to paise (2 dp) so floating-point noise like 44.9000001 can't leak.
    n = Math.round(n * 100) / 100;

    // Show decimals only when there's a fractional part; whole amounts stay clean
    // (e.g. 1200 -> "1,200", 44.9 -> "44.90"). This keeps integer prices tidy
    // while never mangling a decimal value into "4,4.9".
    var fixed = (n % 1 !== 0) ? n.toFixed(2) : n.toFixed(0);

    var parts = fixed.split('.');
    var intPart = parts[0];
    var decPart = parts.length > 1 ? '.' + parts[1] : '';

    // Indian digit grouping applies to the INTEGER part only:
    // rightmost 3 digits, then groups of 2 (e.g. 1234567 -> "12,34,567").
    var lastThree = intPart.length > 3 ? intPart.slice(-3) : intPart;
    var rest = intPart.length > 3 ? intPart.slice(0, -3) : '';
    if (rest !== '') {
        lastThree = rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + lastThree;
    }

    return (isNegative ? '-' : '') + lastThree + decPart;
}

function setupReadMoreLinks(){
    /* Implement height limit and read more text for elements with .readMore */
    var max = 450;
    $(".readMore").each(function() {

        if($(this).data('readMoreLength')){
            max = $(this).data('read-more-length');
        }

        var str = $(this).text();
        if ($.trim(str).length > max) {
            var subStr = str.substring(0, max);
            var hiddenStr = str.substring(max, $.trim(str).length);
            $(this).empty().html(subStr);
            $(this).append(' <a href="javascript:void(0);" class="readMoreLink">Read more…</a>');
            $(this).append('<span class="addText">' + hiddenStr + '</span>');
        }
    });
    $(".readMoreLink").click(function() {
        $(this).siblings(".addText").contents().unwrap();
        $(this).remove();

        // re-create and re-initialize the scroll magic effect
        setupScrollMagicEffect();
    });
}