<?php
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
?>
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
                                <h1 class="text-n address-name"><?=$row['name']?> <span class="address-type <?=$row['type']?>-address"><?=ucfirst($row['type'])?></span></h1>
                                <p class="text-n address-text"><?=$row['address']?></p>
                                <!-- <p class="text-n address-text">Mobile: <strong><?=$row['mobile']?></strong></p> -->
                                <p class="text-n address-text">Mobile: <?=$row['mobile']?> </p>
                                <?=
                                    (isset($row['alternate_mobile']) && !empty($row['alternate_mobile'])) ? '<p class="text-n address-text">Alternate Mobile: ' . $row['alternate_mobile'] . '</p>' : '';
                                ?>
                                <!-- <p class="text-n address-text">Pay on Delivery Available</p> -->
                                <div>
                                    
                                    <button class="cretzo btn btn-light address-action-btn address-action-btn-remove" data-id="<?= $row['id'] ?>">REMOVE</button>
                                    
                                    <button class="cretzo btn btn-light address-action-btn address-action-btn-edit" data-row="<?= htmlspecialchars(json_encode($row)) ?>">EDIT</button>

                                    <?php
                                        if(!$is_default){
                                    ?>
                                        <button class="cretzo btn <?= $is_default ? 'btn-dark' : 'btn-light' ?> address-action-btn address-action-btn-default" data-id="<?= $row['id'] ?>" <?= $is_default ? 'disabled' : ''?>><?= $is_default ? 'Default' : 'Set as Default'?></button>
                                    <?php
                                        }
                                    ?>
                                    
                                </div>
                            </li>
                        </ul>
            <?php   }
                }
            ?>
            
            <!-- <h1 class="text-s">DEFAULT ADDRESS</h1>
            <ul class="list cart-left-two-left cart-left-two-left-upper">
                <li class="address-container selected-address">
                    <h1 class="text-n address-name">Shubham Nagar <span class="home-address">HOME</span></h1>
                    <p class="text-n address-text">1260 sector 14, Escort company Faridabad, Haryana - 121007</p>
                    <p class="text-n address-text">Mobile: <strong>9871233530</strong></p>
                    <p class="text-n address-text">Pay on Delivery Available</p>
                    <div>
                        <button class="cretzo btn btn-dark" disabled>SELECT</button>
                        <button class="cretzo btn btn-light">REMOVE</button>
                        <button class="cretzo btn btn-light">EDIT</button>
                    </div>
                </li>
            </ul>

            <h1 class="text-s">OTHER ADDRESS</h1>
            <ul class="list cart-left-two-left">
                <li class="address-container">
                    <h1 class="text-n address-name">Shubham Nagar <span class="work-address">WORK</span></h1>
                    <p class="text-n address-text">1260 sector 14, Escort company Faridabad, Haryana - 121007</p>
                    <p class="text-n address-text">Mobile: <strong>9871233530</strong></p>
                    <p class="text-n address-text">Pay on Delivery Available</p>
                    <div>
                        <button class="cretzo btn btn-dark">SELECT</button>
                        <button class="cretzo btn btn-light">REMOVE</button>
                        <button class="cretzo btn btn-light">EDIT</button>
                    </div>
                </li>
            </ul> -->

            <button class="cretzo btn btn-light add-address-btn" data-toggle="modal" data-target="#add-address-modal">+ Add New Address</button>
            
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade edit-modal-lg" id="add-address-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header pt-6 pb-1">
                <h4 class="modal-title w-100 ta-c" id="exampleModalLongTitle"> Add Address </h4>
                <button type="button" class="close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body ps-10 pt-0 pb-6">

                    <form action="<?= base_url('my-account/add-address') ?>" method="POST" id="add-address-form" class="mt-3 px-4">
                        <div class="row">
                            <div class="col-md-12 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="name" class="control-label required"><?= !empty($this->lang->line('name')) ? $this->lang->line('name') : 'Name' ?></label>
                                <input type="text" class="form-control" id="address_name" name="name" placeholder="Name" />
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="mobile_number" class="control-label required"><?= !empty($this->lang->line('mobile_number')) ? $this->lang->line('mobile_number') : 'Mobile Number' ?></label>
                                <input type="text" class="form-control" id="mobile_number" name="mobile" placeholder="Mobile Number" />
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="alternate_mobile" class="control-label"><?= !empty($this->lang->line('alternate_mobile')) ? $this->lang->line('alternate_mobile') : 'Alternate Mobile Number' ?></label>
                                <input type="text" class="form-control" id="alternate_mobile" name="alternate_mobile" placeholder="Alternate Mobile Number" />
                            </div>
                            <div class="col-md-12 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="address" class="control-label required"><?= !empty($this->lang->line('address')) ? $this->lang->line('address') : 'Address' ?></label>
                                <textarea name="address" class="form-control" id="address" cols="30" rows="4" placeholder="#Door no, Street Address, Locality, Area, Pincode"></textarea>
                            </div>
                            <!-- Pincode first: entering it auto-fills city, district and state -->
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="pincode" class="control-label required"><?= !empty($this->lang->line('pincode')) ? $this->lang->line('pincode') : 'Zipcode' ?></label>
                                <input type="text" class="form-control" id="pincode" name="pincode" placeholder="Zipcode" maxlength="6" inputmode="numeric" pattern="[0-9]*" />
                                <small id="pincode_status" class="form-text text-muted"></small>
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="city_name" class="control-label required"><?= !empty($this->lang->line('city')) ? $this->lang->line('city') : 'City' ?></label>
                                <input type="text" class="form-control" id="city_name" name="city_name" placeholder="City" />
                                <input type="hidden" name="city_id" id="city" value="" />
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="district" class="control-label required">District</label>
                                <input type="text" class="form-control" id="district" name="general_area_name" placeholder="District" />
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="state" class="control-label required"><?= !empty($this->lang->line('state')) ? $this->lang->line('state') : 'State' ?></label>
                                <select class="form-control" id="state" name="state">
                                    <option value="">-- Select State --</option>
                                    <?php foreach ($indian_states as $state_name) { ?>
                                        <option value="<?= $state_name ?>"><?= $state_name ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <!-- Country section commented out - currently only India is supported
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="country" class="control-label required"><?= !empty($this->lang->line('country')) ? $this->lang->line('country') : 'Country' ?></label>
                                <input type="text" class="form-control" name="country" id="country" placeholder="Country" />
                            </div>
                            -->
                            <input type="hidden" name="country" id="country" value="India" />
                            <div class="col-md-12 col-sm-12 col-xs-12 form-group mb-4 mt-2">
                                <label for="country" class="control-label"><?= !empty($this->lang->line('type')) ? $this->lang->line('type') : 'Type : ' ?></label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="type" id="home" value="home" />
                                    <label for="home" class="form-check-label text-dark"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="type" id="office" value="office" placeholder="Office" />
                                    <label for="office" class="form-check-label text-dark"><?= !empty($this->lang->line('office')) ? $this->lang->line('office') : 'Office' ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="type" id="other" value="other" placeholder="Other" />
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

<!-- Edit Address Modal - mirrors the Add Address modal (same layout + pincode auto-fill) -->
<div class="modal fade edit-modal-lg" id="edit-address-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header pt-6 pb-1">
                <h4 class="modal-title w-100 ta-c" id="exampleModalLongTitle"><?= !empty($this->lang->line('edit_address')) ? $this->lang->line('edit_address') : 'Edit Address' ?></h4>
                <button type="button" class="close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body ps-10 pt-0 pb-6">

                    <form action="<?= base_url('my-account/edit-address') ?>" method="POST" id="edit-address-form" class="mt-3 px-4">
                        <input type="hidden" name="id" id="address_id" value="" />
                        <div class="row">
                            <div class="col-md-12 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="edit_name" class="control-label required"><?= !empty($this->lang->line('name')) ? $this->lang->line('name') : 'Name' ?></label>
                                <input type="text" class="form-control" id="edit_name" name="name" placeholder="Name" />
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="edit_mobile" class="control-label required"><?= !empty($this->lang->line('mobile_number')) ? $this->lang->line('mobile_number') : 'Mobile Number' ?></label>
                                <input type="text" class="form-control" id="edit_mobile" name="mobile" placeholder="Mobile Number" />
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="edit_alternate_mobile" class="control-label"><?= !empty($this->lang->line('alternate_mobile')) ? $this->lang->line('alternate_mobile') : 'Alternate Mobile Number' ?></label>
                                <input type="text" class="form-control" id="edit_alternate_mobile" name="alternate_mobile" placeholder="Alternate Mobile Number" />
                            </div>
                            <div class="col-md-12 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="edit_address" class="control-label required"><?= !empty($this->lang->line('address')) ? $this->lang->line('address') : 'Address' ?></label>
                                <textarea name="address" class="form-control" id="edit_address" cols="30" rows="4" placeholder="#Door no, Street Address, Locality, Area, Pincode"></textarea>
                            </div>
                            <!-- Pincode first: entering it auto-fills city, district and state -->
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="edit_pincode" class="control-label required"><?= !empty($this->lang->line('pincode')) ? $this->lang->line('pincode') : 'Zipcode' ?></label>
                                <input type="text" class="form-control" id="edit_pincode" name="pincode" placeholder="Zipcode" maxlength="6" inputmode="numeric" pattern="[0-9]*" />
                                <small id="edit_pincode_status" class="form-text text-muted"></small>
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="edit_city_name" class="control-label required"><?= !empty($this->lang->line('city')) ? $this->lang->line('city') : 'City' ?></label>
                                <input type="text" class="form-control" id="edit_city_name" name="city_name" placeholder="City" />
                                <input type="hidden" name="city_id" id="edit_city" value="" />
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="edit_district" class="control-label required">District</label>
                                <input type="text" class="form-control" id="edit_district" name="general_area_name" placeholder="District" />
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group mb-3">
                                <label for="edit_state" class="control-label required"><?= !empty($this->lang->line('state')) ? $this->lang->line('state') : 'State' ?></label>
                                <select class="form-control" id="edit_state" name="state">
                                    <option value="">-- Select State --</option>
                                    <?php foreach ($indian_states as $state_name) { ?>
                                        <option value="<?= $state_name ?>"><?= $state_name ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <input type="hidden" name="country" id="edit_country" value="India" />
                            <div class="col-md-12 col-sm-12 col-xs-12 form-group mb-4 mt-2">
                                <label class="control-label"><?= !empty($this->lang->line('type')) ? $this->lang->line('type') : 'Type : ' ?></label>
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

                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <input type="submit" class="cretzo btn btn-dark btn-primary btn-sm d-flex m-auto px-16" id="edit-address-submit-btn" value="Save" />
                            </div>
                            <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                                <div id="edit-address-result"></div>
                            </div>
                        </div>
                    </form>

            </div>
        </div>
    </div>
</div>

<!-- <script>
    window.editAddress = {
        'click .edit-address': function(e, value, row, index) {
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
            }        }
    };
</script> -->