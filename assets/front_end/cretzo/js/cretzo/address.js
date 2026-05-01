$(document).ready(function() {
    setupActionButtons();
    initAddressSearchables();
    checkForAddOrEditAddressInQuery();
});

// ── Searchable dropdown factory (same as seller form) ─────────────────────────
function makeSearchable(searchId, hiddenId, dropdownId, data, onSelect) {
    const searchEl   = document.getElementById(searchId);
    const hiddenEl   = document.getElementById(hiddenId);
    const dropdownEl = document.getElementById(dropdownId);
    if (!searchEl || !hiddenEl || !dropdownEl) return { setData: function(){} };

    if (hiddenEl.value) searchEl.value = hiddenEl.value;

    function renderDropdown(items) {
        dropdownEl.innerHTML = '';
        if (!items.length) { dropdownEl.style.display = 'none'; return; }
        items.forEach(function(item) {
            const div = document.createElement('div');
            div.textContent = item.label;
            div.style.cssText = 'padding:8px 12px; cursor:pointer; font-size:14px;';
            div.addEventListener('mouseenter', function() { this.style.background = '#f0f0f0'; });
            div.addEventListener('mouseleave', function() { this.style.background = '#fff'; });
            div.addEventListener('mousedown', function(e) {
                e.preventDefault();
                searchEl.value  = item.label;
                hiddenEl.value  = item.label;
                dropdownEl.style.display = 'none';
                if (onSelect) onSelect(item);
            });
            dropdownEl.appendChild(div);
        });
        dropdownEl.style.display = 'block';
    }

    searchEl.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        if (!q) { dropdownEl.style.display = 'none'; hiddenEl.value = ''; return; }
        const matches = data.filter(function(item) { return item.label.toLowerCase().includes(q); });
        // If no match from DB, allow manual entry
        hiddenEl.value = this.value;
        renderDropdown(matches);
    });

    searchEl.addEventListener('blur', function() {
        setTimeout(function() { dropdownEl.style.display = 'none'; }, 150);
    });

    return {
        setData: function(newData, selectedLabel) {
            data = newData;
            dropdownEl.style.display = 'none';
            searchEl.value = selectedLabel || '';
            hiddenEl.value = selectedLabel || '';
        },
        clear: function() {
            data = [];
            searchEl.value = '';
            hiddenEl.value = '';
            dropdownEl.style.display = 'none';
        }
    };
}

// ── Load state data once ──────────────────────────────────────────────────────
var addressStateData = [];
$.getJSON(base_url + 'my-account/get-states', function(rows) {
    addressStateData = (rows || []).map(function(r) { return { label: r.name, id: r.id }; });
    // Re-init after states load so dropdowns have data
    initAddressSearchables();
});

// ── Wire up both modals ───────────────────────────────────────────────────────
var addDistrict, addCity, addPincode;
var editDistrict, editCity, editPincode;

function initAddressSearchables() {

    // ADD MODAL
    makeSearchable('state_search', 'state_hidden', 'state_dropdown', addressStateData, function(item) {
        addDistrict.clear(); addCity.clear(); addPincode.clear();
        $.getJSON(base_url + 'my-account/get-districts-by-state', { state_id: item.id }, function(rows) {
            addDistrict.setData(rows.map(function(r) { return { label: r.name, id: r.id }; }), '');
        });
    });

    addDistrict = makeSearchable('district_search', 'district_hidden', 'district_dropdown', [], function(item) {
        addCity.clear(); addPincode.clear();
        var stateLabel = document.getElementById('state_hidden').value;
        var stateItem  = addressStateData.find(function(s) { return s.label === stateLabel; });
        if (!stateItem) return;
        $.getJSON(base_url + 'my-account/get-cities-by-district', { state_id: stateItem.id, district_id: item.id }, function(rows) {
            addCity.setData(rows.map(function(r) { return { label: r.name, id: r.id }; }), '');
        });
    });

    addCity = makeSearchable('city_search', 'city_hidden', 'city_dropdown', [], function(item) {
        addPincode.clear();
        $.getJSON(base_url + 'my-account/get-pincodes-by-city', { city_id: item.id }, function(rows) {
            addPincode.setData(rows.map(function(r) { return { label: r.name, id: r.id }; }), '');
        });
    });

    addPincode = makeSearchable('pincode_search', 'pincode_hidden', 'pincode_dropdown', [], null);

    // EDIT MODAL
    makeSearchable('edit_state_search', 'edit_state_hidden', 'edit_state_dropdown', addressStateData, function(item) {
        editDistrict.clear(); editCity.clear(); editPincode.clear();
        $.getJSON(base_url + 'my-account/get-districts-by-state', { state_id: item.id }, function(rows) {
            editDistrict.setData(rows.map(function(r) { return { label: r.name, id: r.id }; }), '');
        });
    });

    editDistrict = makeSearchable('edit_district_search', 'edit_district_hidden', 'edit_district_dropdown', [], function(item) {
        editCity.clear(); editPincode.clear();
        var stateLabel = document.getElementById('edit_state_hidden').value;
        var stateItem  = addressStateData.find(function(s) { return s.label === stateLabel; });
        if (!stateItem) return;
        $.getJSON(base_url + 'my-account/get-cities-by-district', { state_id: stateItem.id, district_id: item.id }, function(rows) {
            editCity.setData(rows.map(function(r) { return { label: r.name, id: r.id }; }), '');
        });
    });

    editCity = makeSearchable('edit_city_search', 'edit_city_hidden', 'edit_city_dropdown', [], function(item) {
        editPincode.clear();
        $.getJSON(base_url + 'my-account/get-pincodes-by-city', { city_id: item.id }, function(rows) {
            editPincode.setData(rows.map(function(r) { return { label: r.name, id: r.id }; }), '');
        });
    });

    editPincode = makeSearchable('edit_pincode_search', 'edit_pincode_hidden', 'edit_pincode_dropdown', [], null);

    // Pincode is often typed manually as 6 digits — sync search input directly to hidden field on every keystroke
    document.getElementById('pincode_search').addEventListener('input', function() {
        document.getElementById('pincode_hidden').value = this.value;
    });
    document.getElementById('edit_pincode_search').addEventListener('input', function() {
        document.getElementById('edit_pincode_hidden').value = this.value;
    });
}
// ── Pre-fill edit modal when EDIT button clicked ──────────────────────────────
function updateEditAddressForm(row) {
    $("#edit-address-form")[0].reset();
    $("#address_id").val(row.id);
    $("#edit_name").val(row.name);
    $("#edit_area").val(row.area || row.general_area_name || '');
    $("#edit_mobile").val(row.mobile);
    $("#edit_address").val(row.address);
    $("#edit_country").val(row.country);
    if (row.type) $('input[name="type"][value="' + row.type.toLowerCase() + '"]').prop('checked', true);

    // Pre-fill state
    editDistrict.clear(); editCity.clear(); editPincode.clear();
    document.getElementById('edit_state_search').value = row.state || '';
    document.getElementById('edit_state_hidden').value = row.state || '';

    if (!row.state) return;
    var stateItem = addressStateData.find(function(s) { return s.label === row.state; });
    if (!stateItem) return;

    $.getJSON(base_url + 'my-account/get-districts-by-state', { state_id: stateItem.id }, function(rows) {
        editDistrict.setData(rows.map(function(r) { return { label: r.name, id: r.id }; }), row.district || '');
        if (!row.district) return;
        var distItem = rows.find(function(r) { return r.name === row.district; });
        if (!distItem) return;
        $.getJSON(base_url + 'my-account/get-cities-by-district', { state_id: stateItem.id, district_id: distItem.id }, function(cityRows) {
            editCity.setData(cityRows.map(function(r) { return { label: r.name, id: r.id }; }), row.city || '');
            if (!row.city) return;
            var cityItem = cityRows.find(function(r) { return r.name === row.city; });
            if (!cityItem) return;
            $.getJSON(base_url + 'my-account/get-pincodes-by-city', { city_id: cityItem.id }, function(pinRows) {
                editPincode.setData(pinRows.map(function(r) { return { label: r.name, id: r.id }; }), row.pincode || '');
            });
        });
    });
}

// ── Action buttons (edit/remove/default) ─────────────────────────────────────
function setupActionButtons() {
    $('.address-action-btn-edit').click(function(e) {
        e.preventDefault();
        updateEditAddressForm($(this).data("row"));
        $("#edit-address-modal").modal('show');
    });
    $('.address-action-btn-default').click(function(e) {
        e.preventDefault();
        confirm("Set this as default address?") && $.ajax({
            type: "POST", url: base_url + "my-account/set-default-address",
            data: { id: $(this).data("id"), [csrfName]: csrfHash }, dataType: "json",
            success: function(e) { csrfName = e.csrfName; csrfHash = e.csrfHash; e.error == 0 ? (Toast.fire({ icon: "success", title: e.message }), window.location.reload()) : Toast.fire({ icon: "error", title: e.message }); }
        });
    });
    $('.address-action-btn-remove').click(function(e) {
        e.preventDefault();
        confirm("Delete this address?") && $.ajax({
            type: "POST", url: base_url + "my-account/delete-address",
            data: { id: $(this).data("id"), [csrfName]: csrfHash }, dataType: "json",
            success: function(e) { csrfName = e.csrfName; csrfHash = e.csrfHash; e.error == 0 ? window.location.reload() : Toast.fire({ icon: "error", title: e.message }); }
        });
    });
}

function checkForAddOrEditAddressInQuery() {
    const urlParams = new URLSearchParams(window.location.search);
    const idParam = urlParams.get("id");
    if (idParam) {
        $(".address-action-btn-edit").each(function() {
            if ($(this).data("row").id == idParam) $(this).click();
        });
        return;
    }
    if (urlParams.get("action") === "add") $(".add-address-btn").click();
}

// ── Form submissions ──────────────────────────────────────────────────────────
$("#add-address-form").on("submit", function(e) {
    e.preventDefault();
    var t = new FormData(this);
    t.append(csrfName, csrfHash);
    $.ajax({
        type: "POST", data: t, url: $(this).attr("action"), dataType: "json", cache: false, contentType: false, processData: false,
        beforeSend: function() { $("#save-address-submit-btn").val("Please Wait...").attr("disabled", true); },
        success: function(e) {
            csrfName = e.csrfName; csrfHash = e.csrfHash;
            if (e.error == 0) {
                $("#save-address-result").html("<div class='alert alert-success'>" + e.message + "</div>").show().delay(1500).fadeOut();
                setTimeout(function() {
                    $("#add-address-modal").modal("hide");
                    $("#add-address-form")[0].reset();
                    const p = new URLSearchParams(window.location.search);
                    p.get("redirect") === "checkout" ? window.location.href = base_url + 'cart/checkout?id=' + e.data[0]['id'] : window.location.reload();
                }, 2000);
            } else {
                $("#save-address-result").html("<div class='alert alert-danger'>" + e.message + "</div>").show().delay(3000).fadeOut();
                $("#save-address-submit-btn").val("Add Address").attr("disabled", false);
            }
        }
    });
});

$("#edit-address-form").on("submit", function(e) {
    e.preventDefault();
    var t = new FormData(this);
    t.append(csrfName, csrfHash);
    $.ajax({
        type: "POST", data: t, url: $(this).attr("action"), dataType: "json", cache: false, contentType: false, processData: false,
        beforeSend: function() { $("#edit-address-submit-btn").val("Please Wait...").attr("disabled", true); },
        success: function(e) {
            csrfName = e.csrfName; csrfHash = e.csrfHash;
            if (e.error == 0) {
                $("#edit-address-result").html("<div class='alert alert-success'>" + e.message + "</div>").show().delay(1500).fadeOut();
                setTimeout(function() {
                    $("#edit-address-modal").modal("hide");
                    $("#edit-address-form")[0].reset();
                    const p = new URLSearchParams(window.location.search);
                    p.get("redirect") === "checkout" ? window.location.href = base_url + 'cart/checkout?id=' + p.get("id") : window.location.reload();
                }, 2000);
            } else {
                $("#edit-address-result").html("<div class='alert alert-danger'>" + e.message + "</div>").show().delay(3000).fadeOut();
                $("#edit-address-submit-btn").val("Save").attr("disabled", false);
            }
        }
    });
});