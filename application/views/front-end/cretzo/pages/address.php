<style>
/* Allow searchable dropdowns to overflow modal boundaries without clipping */
#add-address-modal .modal-body,
#add-address-modal .modal-content,
#edit-address-modal .modal-body,
#edit-address-modal .modal-content {
    overflow: visible;
}
 
/* Searchable dropdown item styling */
#state_dropdown div, #district_dropdown div, #city_dropdown div, #pincode_dropdown div,
#edit_state_dropdown div, #edit_district_dropdown div, #edit_city_dropdown div, #edit_pincode_dropdown div {
    padding: 8px 12px;
    cursor: pointer;
    font-size: 14px;
    background: #fff;
}

#state_dropdown div:hover, #district_dropdown div:hover, #city_dropdown div:hover, #pincode_dropdown div:hover,
#edit_state_dropdown div:hover, #edit_district_dropdown div:hover, #edit_city_dropdown div:hover, #edit_pincode_dropdown div:hover {
    background: #f0f0f0;
}
</style>

<div class="overview-side-container">
    <h1 class="heading-b">Account</h1>
    <p class="text-n"><?= $users->username ?></p>
    <div class="overview-container">

        <?php $this->load->view('front-end/' . THEME . '/partials/my-account-sidebar', ['active_menu' => $main_page]); ?>
        
        <div class="overview-right">
            <h1 class="heading-n overview-right-heading mb-8">Saved Address</h1>

            <?php 
                if (!empty($addresses['rows'])) {
                    $display_default_header = false;
                    foreach ($addresses['rows'] as $key => $row) {

                        $is_default = $row['is_default'] == 1;

                        if($key == 0 && $is_default){
                            echo '<h1 class="text-s">DEFAULT ADDRESS</h1>';
                            $display_default_header = true;
                        }
                        else if($key == 1){
                            if($display_default_header){
                                echo '<h1 class="text-s">OTHER ADDRESS</h1>';
                            }
                        }
            ?>
                        <ul class="list cart-left-two-left <?= $is_default ? 'cart-left-two-left-upper' : '';?>">
                            <li class="address-container <?=$key == 0 ? 'selected-address' : ''?>" data-row="<?= htmlspecialchars(json_encode($row)) ?>">
                                <h1 class="text-n address-name"><?=$row['name']?> <span class="address-type <?=$row['type']?>-address"><?=$row['type']?></span></h1>
                                <p class="text-n address-text"><?=$row['address']?></p>
                                <p class="text-n address-text">Mobile: <?=$row['mobile']?> </p>
                                <?=
                                    (isset($row['alternate_mobile']) && !empty($row['alternate_mobile'])) ? '<p class="text-n address-text">Alternate Mobile: ' . $row['alternate_mobile'] . '</p>' : '';
                                ?>
                                <div>
                                    <button class="cretzo btn btn-light address-action-btn address-action-btn-remove" data-id="<?= $row['id'] ?>">REMOVE</button>
                                    <button class="cretzo btn btn-light address-action-btn address-action-btn-edit" data-row="<?= htmlspecialchars(json_encode($row)) ?>">EDIT</button>
                                    <?php if(!$is_default){ ?>
                                        <button class="cretzo btn <?= $is_default ? 'btn-dark' : 'btn-light' ?> address-action-btn address-action-btn-default" data-id="<?= $row['id'] ?>" <?= $is_default ? 'disabled' : ''?>><?= $is_default ? 'Default' : 'Set as Default'?></button>
                                    <?php } ?>
                                </div>
                            </li>
                        </ul>
            <?php   }
                }
            ?>

            <button class="cretzo btn btn-light add-address-btn" data-toggle="modal" data-target="#add-address-modal">+ Add New Address</button>
            
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     ADD ADDRESS MODAL
     All state/district/city/pincode use searchable text inputs
     (same pattern as seller profile form — no Select2, no jQuery cascade)
     Hidden inputs carry the actual values submitted to add-address endpoint
════════════════════════════════════════════════════════════════ -->
<div class="modal fade edit-modal-lg" id="add-address-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header pt-6 pb-1">
                <h4 class="modal-title w-100 ta-c" id="exampleModalLongTitle"> Add Address </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body ps-10 pt-0 pb-6">
                <form action="<?= base_url('my-account/add-address') ?>" method="POST" id="add-address-form" class="mt-3 px-4">
                    <div class="row">

                        <!-- Name -->
                        <div class="col-md-12 col-sm-12 col-xs-12 form-group mb-3">
                            <label for="address_name" class="control-label required"><?= !empty($this->lang->line('name')) ? $this->lang->line('name') : 'Name' ?></label>
                            <input type="text" class="form-control" id="address_name" name="name" placeholder="Name" />
                        </div>

                        <!-- Mobile -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="control-label required"><?= !empty($this->lang->line('mobile_number')) ? $this->lang->line('mobile_number') : 'Mobile Number' ?></label>
                            <input type="text" class="form-control" id="mobile_number" name="mobile" placeholder="Mobile Number" />
                        </div>

                        <!-- Alternate Mobile -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="control-label"><?= !empty($this->lang->line('alternate_mobile')) ? $this->lang->line('alternate_mobile') : 'Alternate Mobile Number' ?></label>
                            <input type="text" class="form-control" id="alternate_mobile" name="alternate_mobile" placeholder="Alternate Mobile Number" />
                        </div>

                        <!-- Address -->
                        <div class="col-md-12 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="control-label required"><?= !empty($this->lang->line('address')) ? $this->lang->line('address') : 'Address' ?></label>
                            <textarea name="address" class="form-control" id="address" cols="30" rows="4" placeholder="#Door no, Street Address, Locality, Area, Pincode"></textarea>
                        </div>

                        <!-- State — searchable text input, hidden carries value to backend -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="control-label required">State</label>
                            <div style="position:relative;">
                                <input type="text" id="state_search" class="form-control" placeholder="Search State..." autocomplete="off">
                                <input type="hidden" name="state" id="state_hidden" required>
                                <div id="state_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:9999; width:100%;"></div>
                            </div>
                        </div>

                        <!-- District — populated after state is selected -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="control-label required">District</label>
                            <div style="position:relative;">
                                <input type="text" id="district_search" class="form-control" placeholder="Search District..." autocomplete="off">
                                <input type="hidden" name="district" id="district_hidden">
                                <div id="district_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:9999; width:100%;"></div>
                            </div>
                        </div>

                        <!-- City — populated after district is selected -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="control-label">City</label>
                            <div style="position:relative;">
                                <input type="text" id="city_search" class="form-control" placeholder="Search City..." autocomplete="off">
                                <input type="hidden" name="city_name" id="city_hidden">
                                <div id="city_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:9999; width:100%;"></div>
                            </div>
                        </div>

                        <!-- Pincode — populated from zipcodes table after city selected, manual entry allowed -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="control-label required">Pincode</label>
                            <div style="position:relative;">
                                <input type="text" id="pincode_search" class="form-control" placeholder="Search or type Pincode..." autocomplete="off">
                                <input type="hidden" name="pincode" id="pincode_hidden" required>
                                <div id="pincode_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:9999; width:100%;"></div>
                            </div>
                        </div>

                        <!-- Area — free text, no cascade needed -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="control-label">Area</label>
                            <input type="text" class="form-control" id="area" name="general_area_name" placeholder="Area Name" />
                        </div>

                        <!-- Country -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="control-label required"><?= !empty($this->lang->line('country')) ? $this->lang->line('country') : 'Country' ?></label>
                            <input type="text" class="form-control" name="country" id="country" placeholder="Country" />
                        </div>

                        <!-- Address Type -->
                        <div class="col-md-12 col-sm-12 col-xs-12 form-group mb-4 mt-2">
                            <label class="control-label"><?= !empty($this->lang->line('type')) ? $this->lang->line('type') : 'Type : ' ?></label>
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="type" id="home" value="home" />
                                <label for="home" class="form-check-label text-dark"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="type" id="office" value="office" />
                                <label for="office" class="form-check-label text-dark"><?= !empty($this->lang->line('office')) ? $this->lang->line('office') : 'Office' ?></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="type" id="other" value="other" />
                                <label for="other" class="form-check-label text-dark"><?= !empty($this->lang->line('other')) ? $this->lang->line('other') : 'Other' ?></label>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <input type="submit" class="cretzo btn btn-dark btn-primary btn-sm d-flex m-auto px-16" id="save-address-submit-btn" value="Add Address" />
                        </div>
                        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                            <div id="save-address-result"></div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     EDIT ADDRESS MODAL
     Same searchable pattern as Add modal — prefilled by address.js
     updateEditAddressForm() when EDIT button is clicked
     edit_ prefix on all IDs to avoid conflict with Add modal
════════════════════════════════════════════════════════════════ -->
<div class="modal fade edit-modal-lg" id="edit-address-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-6">
                <h5 class="modal-title"><?= !empty($this->lang->line('edit_address')) ? $this->lang->line('edit_address') : 'Edit Address' ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#edit-address-modal').modal('hide');">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body ps-10 pt-0">
                <form action="<?= base_url('my-account/edit-address') ?>" method="POST" id="edit-address-form" class="mt-4">
                    <!-- Hidden address ID, filled by updateEditAddressForm() in address.js -->
                    <input type="hidden" name="id" id="address_id" value="" />
                    <div class="row">

                        <!-- Name -->
                        <div class="col-md-4 col-sm-12 col-xs-12 form-group">
                            <label class="form-check-label required"><?= !empty($this->lang->line('name')) ? $this->lang->line('name') : 'Name' ?></label>
                            <input type="text" class="form-control" id="edit_name" name="name" placeholder="Name" />
                        </div>

                        <!-- Mobile -->
                        <div class="col-md-4 col-sm-12 col-xs-12 form-group">
                            <label class="form-check-label required"><?= !empty($this->lang->line('mobile_number')) ? $this->lang->line('mobile_number') : 'Mobile Number' ?></label>
                            <input type="text" class="form-control" id="edit_mobile" name="mobile" placeholder="Mobile Number" />
                        </div>

                        <!-- Address -->
                        <div class="col-sm-12 col-xs-12 form-group">
                            <label class="form-check-label required"><?= !empty($this->lang->line('address')) ? $this->lang->line('address') : 'Address' ?></label>
                            <input type="text" class="form-control" name="address" id="edit_address" placeholder="Address" />
                        </div>

                        <!-- State — searchable, prefilled by updateEditAddressForm() -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="form-check-label required">State</label>
                            <div style="position:relative;">
                                <input type="text" id="edit_state_search" class="form-control" placeholder="Search State..." autocomplete="off">
                                <input type="hidden" name="state" id="edit_state_hidden" required>
                                <div id="edit_state_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:9999; width:100%;"></div>
                            </div>
                        </div>

                        <!-- District — populated after state selected or prefilled -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="form-check-label required">District</label>
                            <div style="position:relative;">
                                <input type="text" id="edit_district_search" class="form-control" placeholder="Search District..." autocomplete="off">
                                <input type="hidden" name="district" id="edit_district_hidden">
                                <div id="edit_district_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:9999; width:100%;"></div>
                            </div>
                        </div>

                        <!-- City — populated after district selected or prefilled -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="form-check-label">City</label>
                            <div style="position:relative;">
                                <input type="text" id="edit_city_search" class="form-control" placeholder="Search City..." autocomplete="off">
                                <input type="hidden" name="city_name" id="edit_city_hidden">
                                <div id="edit_city_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:9999; width:100%;"></div>
                            </div>
                        </div>

                        <!-- Pincode — populated from zipcodes table or typed manually -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                            <label class="form-check-label required">Pincode</label>
                            <div style="position:relative;">
                                <input type="text" id="edit_pincode_search" class="form-control" placeholder="Search or type Pincode..." autocomplete="off">
                                <input type="hidden" name="pincode" id="edit_pincode_hidden" required>
                                <div id="edit_pincode_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:9999; width:100%;"></div>
                            </div>
                        </div>

                        <!-- Area — free text -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group">
                            <label class="control-label">Area</label>
                            <input type="text" class="form-control" id="edit_area" name="edit_general_area_name" placeholder="Area Name" />
                        </div>

                        <!-- Country -->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group">
                            <label class="form-check-label required"><?= !empty($this->lang->line('country')) ? $this->lang->line('country') : 'Country' ?></label>
                            <input type="text" class="form-control" name="country" id="edit_country" placeholder="Country" />
                        </div>

                        <!-- Address Type -->
                        <div class="col-md-12 col-sm-12 col-xs-12 form-group">
                            <label class="form-check-label"><?= !empty($this->lang->line('type')) ? $this->lang->line('type') : 'Type : ' ?></label>
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="type" id="edit_home" value="home" />
                                <label for="edit_home" class="form-check-label text-dark"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="type" id="edit_office" value="office" />
                                <label for="edit_office" class="form-check-label text-dark"><?= !empty($this->lang->line('office')) ? $this->lang->line('office') : 'Office' ?></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="type" id="edit_other" value="other" />
                                <label for="edit_other" class="form-check-label text-dark"><?= !empty($this->lang->line('other')) ? $this->lang->line('other') : 'Other' ?></label>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                            <input type="submit" class="cretzo btn btn-dark btn-primary btn-sm" id="edit-address-submit-btn" value="Save" />
                        </div>
                        <div class="col-md-12 col-sm-12 col-xs-12 text-center mt-2">
                            <div id="edit-address-result"></div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>x
</div>