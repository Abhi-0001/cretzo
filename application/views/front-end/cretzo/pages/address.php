<?php
/**
 * My Account > Saved addresses.
 *
 * Rebuilt on the shared account shell. The add/edit forms were already popups;
 * they keep every id and form id that assets/front_end/cretzo/js/cretzo/address.js
 * binds to (#add-address-form, #edit-address-form, #pincode / #edit_pincode and
 * the fields the pincode lookup fills, #save-address-submit-btn,
 * #edit-address-submit-btn, #save-address-result, #edit-address-result, and the
 * .address-action-btn-{edit,remove,default} buttons with their data-row/data-id),
 * so the endpoints and the India Post pincode auto-fill are untouched.
 *
 * What changed beyond the styling:
 *  - The cards show the WHOLE address (locality, city, district, state, pincode,
 *    landmark, alternate number), not just the free-text first line. Everything
 *    below it was already stored and already used at checkout, but was invisible
 *    here - so a customer could not tell two addresses in the same street apart.
 *  - Remove and Set-as-default confirm in a popup instead of window.confirm().
 */

// List of Indian states and union territories (currently only India is supported)
$indian_states = [
    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
    'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
    'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
    'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
    'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
    'Andaman and Nicobar Islands', 'Chandigarh',
    'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Jammu and Kashmir',
    'Ladakh', 'Lakshadweep', 'Puducherry',
];

$address_rows = (isset($addresses['rows']) && is_array($addresses['rows'])) ? $addresses['rows'] : [];

$type_icons = ['home' => 'uil-home', 'office' => 'uil-building', 'other' => 'uil-map-pin-alt'];

/*
 * Builds the "locality, city, district, state - pincode" line, skipping the
 * parts that are empty or that just repeat one another. Several rows in this
 * install have `area` holding a bare pincode (a legacy of the old area picker),
 * so a part that is identical to the pincode or to an earlier part is dropped
 * rather than printed twice.
 */
function czap_address_locality($row)
{
    $parts = [];
    foreach (['city', 'area', 'state'] as $key) {
        $value = isset($row[$key]) ? trim((string) $row[$key]) : '';
        if ($value === '' || $value === (string) $row['pincode']) {
            continue;
        }
        if (in_array(strtolower($value), array_map('strtolower', $parts), true)) {
            continue;
        }
        $parts[] = $value;
    }
    $line = implode(', ', $parts);
    if (!empty($row['pincode'])) {
        $line .= ($line === '' ? '' : ' - ') . $row['pincode'];
    }
    return $line;
}

/* ---------------------------------------------------------------- actions -- */
ob_start(); ?>
<button type="button" class="czap-btn czap-btn--primary add-address-btn" data-czap-open="#add-address-modal">
    <i class="uil uil-plus"></i> Add new address
</button>
<?php $page_actions = ob_get_clean();

/* ---------------------------------------------------------------- content -- */
ob_start(); ?>

<?php if (empty($address_rows)) { ?>
    <div class="czap-empty">
        <div class="czap-empty__icon"><i class="uil uil-map-marker-plus"></i></div>
        <h3 class="czap-empty__title">No saved addresses yet</h3>
        <p class="czap-empty__text">
            Save an address now and checkout becomes a single tap. You can keep as many as you like
            and pick one per order.
        </p>
        <button type="button" class="czap-btn czap-btn--primary add-address-btn" data-czap-open="#add-address-modal">
            <i class="uil uil-plus"></i> Add your first address
        </button>
    </div>
<?php } else { ?>

    <?php
    /* Default first. The old view relied on the query's own ordering and then
     * printed a "DEFAULT ADDRESS" heading only when row 0 happened to be the
     * default one - so on an account whose default was not first, neither
     * heading appeared and the default card was indistinguishable. */
    usort($address_rows, function ($a, $b) {
        return ((int) $b['is_default']) <=> ((int) $a['is_default']);
    });
    $default_rows = array_values(array_filter($address_rows, function ($r) {
        return (int) $r['is_default'] === 1;
    }));
    $other_rows = array_values(array_filter($address_rows, function ($r) {
        return (int) $r['is_default'] !== 1;
    }));
    ?>

    <?php
    $groups = [];
    if (!empty($default_rows)) {
        $groups[] = ['label' => 'Default address', 'rows' => $default_rows];
    }
    if (!empty($other_rows)) {
        $groups[] = ['label' => empty($default_rows) ? 'Saved addresses' : 'Other addresses', 'rows' => $other_rows];
    }

    foreach ($groups as $group) { ?>
        <p class="czap-sec"><?= $group['label'] ?></p>
        <div class="czap-cols" style="margin-bottom:8px">
            <?php foreach ($group['rows'] as $row) {
                $is_default = ((int) $row['is_default'] === 1);
                $type = strtolower((string) $row['type']);
                $icon = isset($type_icons[$type]) ? $type_icons[$type] : 'uil-map-pin-alt';
                /* One JSON blob per card, read by address.js to fill the edit form. */
                $row_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="czap-addr <?= $is_default ? 'is-default' : '' ?>">
                    <div class="czap-addr__head">
                        <h3 class="czap-addr__name"><?= html_escape($row['name']) ?></h3>
                        <span class="czap-badge czap-badge--brand" style="text-transform:capitalize">
                            <?= html_escape($row['type']) ?>
                        </span>
                        <?php if ($is_default) { ?>
                            <span class="czap-badge czap-badge--ok">Default</span>
                        <?php } ?>
                    </div>

                    <p class="czap-addr__lines">
                        <i class="uil <?= $icon ?>" style="color:var(--czap-orange)"></i>
                        <?= html_escape($row['address']) ?>
                        <?php $locality = czap_address_locality($row);
                        if ($locality !== '') { ?>
                            <br><?= html_escape($locality) ?>
                        <?php } ?>
                        <?php if (!empty($row['landmark'])) { ?>
                            <br><span class="czap-muted">Landmark:</span> <?= html_escape($row['landmark']) ?>
                        <?php } ?>
                    </p>

                    <p class="czap-addr__lines">
                        <i class="uil uil-phone" style="color:var(--czap-orange)"></i>
                        <strong><?= html_escape($row['mobile']) ?></strong>
                        <?php if (!empty($row['alternate_mobile'])) { ?>
                            <span class="czap-muted">&nbsp;/&nbsp;<?= html_escape($row['alternate_mobile']) ?></span>
                        <?php } ?>
                    </p>

                    <div class="czap-addr__actions">
                        <button type="button" class="czap-btn czap-btn--ghost czap-btn--sm address-action-btn address-action-btn-edit"
                                data-row="<?= $row_json ?>">
                            <i class="uil uil-edit-alt"></i> Edit
                        </button>
                        <?php if (!$is_default) { ?>
                            <button type="button" class="czap-btn czap-btn--ghost czap-btn--sm address-action-btn address-action-btn-default"
                                    data-id="<?= (int) $row['id'] ?>">
                                <i class="uil uil-check-circle"></i> Set as default
                            </button>
                        <?php } ?>
                        <button type="button" class="czap-btn czap-btn--danger czap-btn--sm address-action-btn address-action-btn-remove"
                                data-id="<?= (int) $row['id'] ?>"
                                data-name="<?= html_escape($row['name']) ?>">
                            <i class="uil uil-trash-alt"></i> Remove
                        </button>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

<?php }

$page_content = ob_get_clean();

$this->load->view('front-end/' . THEME . '/partials/account-layout', [
    'active_menu'  => $main_page,
    'page_title'   => 'Saved addresses',
    'page_sub'     => 'Where we deliver your orders',
    'page_icon'    => 'uil-map-marker',
    'page_actions' => $page_actions,
    'page_content' => $page_content,
]);

/*
 * ============================================================================
 * The add and edit popups.
 *
 * They are the same form twice over, so the fields are generated from one list -
 * the two used to be hand-maintained copies of each other, which is how the edit
 * form ended up without the alternate-mobile and landmark inputs that the add
 * form had. `$prefix` produces the `edit_`-prefixed ids address.js expects.
 * ============================================================================
 */
$address_forms = [
    [
        'id'          => 'add-address-modal',
        'form_id'     => 'add-address-form',
        'action'      => base_url('my-account/add-address'),
        'title'       => 'Add a new address',
        'sub'         => 'Fill in the pincode first and we will complete the rest.',
        'submit_id'   => 'save-address-submit-btn',
        'submit_text' => 'Add address',
        'result_id'   => 'save-address-result',
        'prefix'      => '',
        'hidden_id'   => false,
    ],
    [
        'id'          => 'edit-address-modal',
        'form_id'     => 'edit-address-form',
        'action'      => base_url('my-account/edit-address'),
        'title'       => !empty($this->lang->line('edit_address')) ? $this->lang->line('edit_address') : 'Edit address',
        'sub'         => 'Changes apply to future orders, not to orders already placed.',
        'submit_id'   => 'edit-address-submit-btn',
        'submit_text' => 'Save changes',
        'result_id'   => 'edit-address-result',
        'prefix'      => 'edit_',
        'hidden_id'   => true,
    ],
];

foreach ($address_forms as $f):
    $p = $f['prefix'];
    /* address.js reads these exact ids. The add form's name/mobile inputs are
       #address_name / #mobile_number rather than #name / #mobile, which is a
       quirk of the original markup that the checkout flow also relies on. */
    $id_name    = ($p === '') ? 'address_name' : 'edit_name';
    $id_mobile  = ($p === '') ? 'mobile_number' : 'edit_mobile';
    $id_alt     = $p . 'alternate_mobile';
    $id_address = ($p === '') ? 'address' : 'edit_address';
    $id_landmark = $p . 'landmark';
    $id_pincode = $p . 'pincode';
    $id_pin_status = $p . 'pincode_status';
    $id_city    = $p . 'city_name';
    $id_city_hidden = ($p === '') ? 'city' : 'edit_city';
    $id_district = $p . 'district';
    $id_state   = $p . 'state';
    $id_country = $p . 'country';
?>
<div class="czap-modal czap-modal--lg" id="<?= $f['id'] ?>" hidden aria-hidden="true"
     role="dialog" aria-modal="true" aria-labelledby="<?= $f['id'] ?>-title">
    <div class="czap-modal__scrim" data-czap-close></div>
    <div class="czap-modal__panel" role="document">

        <form action="<?= $f['action'] ?>" method="POST" id="<?= $f['form_id'] ?>">
            <?php if ($f['hidden_id']) { ?>
                <input type="hidden" name="id" id="address_id" value="">
            <?php } ?>

            <div class="czap-modal__head">
                <div>
                    <h2 class="czap-modal__title" id="<?= $f['id'] ?>-title">
                        <i class="uil uil-map-marker"></i> <?= html_escape($f['title']) ?>
                    </h2>
                    <p class="czap-modal__sub"><?= html_escape($f['sub']) ?></p>
                </div>
                <button type="button" class="czap-modal__x" data-czap-close aria-label="Close">&times;</button>
            </div>

            <div class="czap-modal__body">
                <div class="czap-grid">

                    <div class="czap-field czap-span-2">
                        <label class="czap-field__label" for="<?= $id_name ?>">
                            <?= !empty($this->lang->line('name')) ? $this->lang->line('name') : 'Full name' ?><span class="czap-req">*</span>
                        </label>
                        <input type="text" class="czap-input" id="<?= $id_name ?>" name="name"
                               placeholder="Who should we ask for on delivery?" data-czap-autofocus>
                    </div>

                    <div class="czap-field">
                        <label class="czap-field__label" for="<?= $id_mobile ?>">
                            <?= !empty($this->lang->line('mobile_number')) ? $this->lang->line('mobile_number') : 'Mobile number' ?><span class="czap-req">*</span>
                        </label>
                        <input type="tel" class="czap-input" id="<?= $id_mobile ?>" name="mobile"
                               placeholder="10 digit mobile number" inputmode="numeric" maxlength="10" data-czap-digits>
                    </div>

                    <div class="czap-field">
                        <label class="czap-field__label" for="<?= $id_alt ?>">
                            <?= !empty($this->lang->line('alternate_mobile')) ? $this->lang->line('alternate_mobile') : 'Alternate mobile' ?>
                        </label>
                        <input type="tel" class="czap-input" id="<?= $id_alt ?>" name="alternate_mobile"
                               placeholder="Optional" inputmode="numeric" maxlength="10" data-czap-digits>
                    </div>

                    <div class="czap-field czap-span-2">
                        <label class="czap-field__label" for="<?= $id_address ?>">
                            <?= !empty($this->lang->line('address')) ? $this->lang->line('address') : 'Address' ?><span class="czap-req">*</span>
                        </label>
                        <textarea name="address" class="czap-textarea" id="<?= $id_address ?>" rows="3"
                                  placeholder="Flat / house no, building, street, locality"></textarea>
                    </div>

                    <div class="czap-field czap-span-2">
                        <label class="czap-field__label" for="<?= $id_landmark ?>">
                            <?= !empty($this->lang->line('landmark')) ? $this->lang->line('landmark') : 'Landmark' ?>
                        </label>
                        <input type="text" class="czap-input" id="<?= $id_landmark ?>" name="landmark"
                               placeholder="Optional - e.g. opposite the metro station">
                    </div>

                    <?php // Pincode first: entering it auto-fills city, district and state
                          // from the India Post public API (see setupPincodeAutofill). All
                          // three stay editable so the user can correct the lookup. ?>
                    <div class="czap-field">
                        <label class="czap-field__label" for="<?= $id_pincode ?>">
                            <?= !empty($this->lang->line('pincode')) ? $this->lang->line('pincode') : 'Pincode' ?><span class="czap-req">*</span>
                        </label>
                        <input type="text" class="czap-input" id="<?= $id_pincode ?>" name="pincode"
                               placeholder="6 digit pincode" maxlength="6" inputmode="numeric" pattern="[0-9]*">
                        <small id="<?= $id_pin_status ?>" class="czap-help"></small>
                    </div>

                    <div class="czap-field">
                        <label class="czap-field__label" for="<?= $id_city ?>">
                            <?= !empty($this->lang->line('city')) ? $this->lang->line('city') : 'City' ?><span class="czap-req">*</span>
                        </label>
                        <input type="text" class="czap-input" id="<?= $id_city ?>" name="city_name" placeholder="City">
                        <input type="hidden" name="city_id" id="<?= $id_city_hidden ?>" value="">
                    </div>

                    <div class="czap-field">
                        <label class="czap-field__label" for="<?= $id_district ?>">District<span class="czap-req">*</span></label>
                        <input type="text" class="czap-input" id="<?= $id_district ?>" name="general_area_name" placeholder="District">
                    </div>

                    <div class="czap-field">
                        <label class="czap-field__label" for="<?= $id_state ?>">
                            <?= !empty($this->lang->line('state')) ? $this->lang->line('state') : 'State' ?><span class="czap-req">*</span>
                        </label>
                        <select class="czap-select" id="<?= $id_state ?>" name="state">
                            <option value="">Select a state</option>
                            <?php foreach ($indian_states as $state_name) { ?>
                                <option value="<?= $state_name ?>"><?= $state_name ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <?php // Country is fixed: this store only ships within India, and the
                          // serviceability check downstream assumes it. ?>
                    <input type="hidden" name="country" id="<?= $id_country ?>" value="India">

                    <div class="czap-field czap-span-2">
                        <label class="czap-field__label">
                            <?= !empty($this->lang->line('type')) ? $this->lang->line('type') : 'Address type' ?>
                        </label>
                        <div class="czap-radios">
                            <?php foreach (['home' => 'uil-home', 'office' => 'uil-building', 'other' => 'uil-map-pin-alt'] as $value => $icon) { ?>
                                <label class="czap-radio" for="<?= $p . $value ?>">
                                    <input type="radio" name="type" id="<?= $p . $value ?>" value="<?= $value ?>">
                                    <i class="uil <?= $icon ?>"></i>
                                    <?= !empty($this->lang->line($value)) ? $this->lang->line($value) : ucfirst($value) ?>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <?php /* address.js injects Bootstrap `alert alert-success` / `alert-danger`
                         markup into this box, so it carries no styling of its own. */ ?>
                <div id="<?= $f['result_id'] ?>" style="margin-top:16px"></div>
            </div>

            <div class="czap-modal__foot">
                <button type="button" class="czap-btn czap-btn--quiet" data-czap-close>Cancel</button>
                <?php /* An <input type="submit">, not a <button>: address.js sets its label
                         with .val("Please Wait...") on send and .val("Save") on failure,
                         which only works on an input. */ ?>
                <input type="submit" class="czap-btn czap-btn--primary" id="<?= $f['submit_id'] ?>"
                       value="<?= html_escape($f['submit_text']) ?>">
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
