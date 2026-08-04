<div class="content-wrapper admin-web-settings-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-globe mr-2 text-primary-theme"></i>General Website Settings</h4>
                    <p class="text-muted mb-0 small">Site details, branding, feature sections and theme colours for the storefront.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item active">General Website Settings</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <form class="form-horizontal form-submit-event" action="<?= base_url('admin/setting/update_web_settings') ?>" method="POST" id="system_setting_form" enctype="multipart/form-data">
                        <div class="card attribute-card">
                            <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                                <div class="d-flex align-items-center">
                                    <span class="header-icon bg-set mr-2"><i class="fas fa-info-circle"></i></span>
                                    <h5 class="mb-0">General Details</h5>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="site_title">Site Title <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="site_title" value="<?= (isset($web_settings['site_title'])) ? html_escape($web_settings['site_title']) : '' ?>" placeholder="Prefix title for the website. " />
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="support_number">Support Number <span class='text-danger text-xs'>*</span></label>
                                        <input type="number" class="form-control" name="support_number" value="<?= (isset($web_settings['support_number'])) ? html_escape($web_settings['support_number']) : '' ?>" placeholder="Customer support mobile number" />
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="support_email">Support Email <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="support_email" value="<?= (isset($web_settings['support_email'])) ? html_escape($web_settings['support_email']) : '' ?>" placeholder="Customer support email" />
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="address">Copyright Details <span class='text-danger text-xs'>*</span></label>
                                        <textarea name="copyright_details" id="copyright_details" class="form-control" cols="30" rows="3"><?= (isset($web_settings['copyright_details'])) ? html_escape($web_settings['copyright_details']) : '' ?></textarea>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="address">Address <span class='text-danger text-xs'>*</span></label>
                                        <textarea name="address" id="address" class="form-control" cols="30" rows="5"><?= (isset($web_settings['address'])) ? html_escape($web_settings['address']) : '' ?></textarea>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="app_short_description">Short Description <span class='text-danger text-xs'>*</span></label>
                                        <textarea name="app_short_description" id="app_short_description" class="form-control" cols="30" rows="5"><?= (isset($web_settings['app_short_description'])) ? html_escape($web_settings['app_short_description']) : '' ?></textarea>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="map_iframe">Map Iframe <span class='text-danger text-xs'>*</span></label>
                                        <textarea name="map_iframe" id="map_iframe" class="form-control" cols="30" rows="5"><?= (isset($web_settings['map_iframe'])) ? html_escape($web_settings['map_iframe']) : '' ?></textarea>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <div class="row">
                                            <div class="col-md-6 form-group">
                                                <label for="logo">Logo <span class='text-danger text-xs'>*</span><small>(Recommended Size : larger than 120 x 120 & smaller than 150 x 150 pixels.)</small></label>
                                                <div class="col-sm-10">
                                                    <div class='col-md-3'><a class="uploadFile img btn btn-primary text-white btn-sm" data-input='logo' data-isremovable='0' data-is-multiple-uploads-allowed='0' data-toggle="modal" data-target="#media-upload-modal" value="Upload Photo"><i class='fa fa-upload'></i> Upload</a></div>
                                                    <?php
                                                    if (!empty($logo)) {
                                                    ?>
                                                        <label class="text-danger mt-3">*Only Choose When Update is necessary</label>
                                                        <div class="container-fluid row image-upload-section">
                                                            <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image">
                                                                <div class=''>
                                                                    <div class='upload-media-div'><img class="img-fluid mb-2" src="<?= html_escape(BASE_URL() . $logo) ?>" alt="Image Not Found"></div>
                                                                    <input type="hidden" name="logo" id='logo' value='<?= html_escape($logo) ?>'>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php
                                                    } else { ?>
                                                        <div class="container-fluid row image-upload-section">
                                                            <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image d-none">
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label for="favicon">Favicon <span class='text-danger text-xs'>*</span></label>
                                                <div class="col-sm-10">
                                                    <div class='col-md-3'><a class="uploadFile img btn btn-primary text-white btn-sm" data-input='favicon' data-isremovable='0' data-is-multiple-uploads-allowed='0' data-toggle="modal" data-target="#media-upload-modal" value="Upload Photo"><i class='fa fa-upload'></i> Upload</a></div>
                                                    <?php
                                                    if (!empty($favicon)) {
                                                    ?>
                                                        <label class="text-danger mt-3">*Only Choose When Update is necessary</label>
                                                        <div class="container-fluid row image-upload-section">
                                                            <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image">
                                                                <img class="img-fluid mb-2" src="<?= html_escape(BASE_URL() . $favicon) ?>" alt="Image Not Found">
                                                                <input type="hidden" name="favicon" id='favicon' value='<?= html_escape($favicon) ?>'>
                                                            </div>
                                                        </div>
                                                    <?php
                                                    } else { ?>
                                                        <div class="container-fluid row image-upload-section">
                                                            <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded text-center grow image d-none">
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="support_email">Meta Keywords <span class='text-danger text-xs'>*</span></label>
                                        <textarea name="meta_keywords" id="meta_keywords" class="form-control" cols="30" rows="5"><?= (isset($web_settings['meta_keywords'])) ? html_escape(str_replace(array("\n\r", "\n", "\r", "\\"), "", $web_settings['meta_keywords'])) : '' ?></textarea>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="support_email">Meta Description <span class='text-danger text-xs'>*</span></label>
                                        <textarea name="meta_description" id="meta_description" class="form-control" cols="30" rows="5"><?= (isset($web_settings['meta_description'])) ? html_escape($web_settings['meta_description']) : '' ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card attribute-card">
                            <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                                <div class="d-flex align-items-center">
                                    <span class="header-icon bg-set mr-2"><i class="fas fa-mobile-alt"></i></span>
                                    <h5 class="mb-0">App Download Section</h5>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="row">
                                    <div class="form-group col-md-12 col-sm-12">
                                        <label for="is_delivery_boy_otp_setting_on"> Enable / Disable</label>
                                        <div class="card-body">
                                            <input type="checkbox" name="app_download_section" <?= (isset($web_settings['app_download_section']) && $web_settings['app_download_section'] == '1') ? 'Checked' : ''  ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="app_download_section_title">Title <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="app_download_section_title" value="<?= (isset($web_settings['app_download_section_title'])) ? html_escape($web_settings['app_download_section_title']) : '' ?>" placeholder="App download section title. " />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="app_download_section_tagline">Tagline<span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="app_download_section_tagline" value="<?= (isset($web_settings['app_download_section_tagline'])) ? html_escape($web_settings['app_download_section_tagline']) : '' ?>" placeholder="App download section Tagline." />
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="app_download_section_short_description">Short Description <span class='text-danger text-xs'>*</span></label>
                                        <textarea name="app_download_section_short_description" id="app_download_section_short_description" class="form-control" cols="30" rows="5"><?= (isset($web_settings['app_download_section_short_description'])) ? html_escape($web_settings['app_download_section_short_description']) : '' ?></textarea>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="app_download_section_title">Playstore URL <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="app_download_section_playstore_url" value="<?= (isset($web_settings['app_download_section_playstore_url'])) ? html_escape($web_settings['app_download_section_playstore_url']) : '' ?>" placeholder="Playstore URL. " />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="app_download_section_tagline">Tagline<span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="app_download_section_appstore_url" value="<?= (isset($web_settings['app_download_section_appstore_url'])) ? html_escape($web_settings['app_download_section_appstore_url']) : '' ?>" placeholder="Appstore URL." />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card attribute-card">
                            <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                                <div class="d-flex align-items-center">
                                    <span class="header-icon bg-set mr-2"><i class="fas fa-share-alt"></i></span>
                                    <h5 class="mb-0">Social Media Links</h5>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="twitter_link">Twitter</label>
                                        <input type="text" class="form-control" name="twitter_link" value="<?= (isset($web_settings['twitter_link'])) ? html_escape($web_settings['twitter_link']) : '' ?>" placeholder="Twitter Link" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="facebook_link">Facebook</label>
                                        <input type="text" class="form-control" name="facebook_link" value="<?= (isset($web_settings['facebook_link'])) ? html_escape($web_settings['facebook_link']) : '' ?>" placeholder="Facebook Link" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="instagram_link">Instagram</label>
                                        <input type="text" class="form-control" name="instagram_link" value="<?= (isset($web_settings['instagram_link'])) ? html_escape($web_settings['instagram_link']) : '' ?>" placeholder="Instagram Link" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="youtube_link">Youtube</label>
                                        <input type="text" class="form-control" name="youtube_link" value="<?= (isset($web_settings['youtube_link'])) ? html_escape($web_settings['youtube_link']) : '' ?>" placeholder="Youtube Link" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card attribute-card">
                            <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                                <div class="d-flex align-items-center">
                                    <span class="header-icon bg-set mr-2"><i class="fas fa-star"></i></span>
                                    <h5 class="mb-0">Feature Section &amp; Theme Colours</h5>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="row">
                                    <h4 class="h4 col-md-12">Shipping</h4>
                                    <div class="form-group col-md-2 col-sm-4">
                                        <label for="shipping_mode"> Enable / Disable</label>
                                        <div class="card-body">
                                            <input type="checkbox" name="shipping_mode" <?= (isset($web_settings['shipping_mode']) && $web_settings['shipping_mode'] == '1') ? 'Checked' : ''  ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="shipping_title">Title</label>
                                        <input type="text" class="form-control" name="shipping_title" value="<?= (isset($web_settings['shipping_title'])) ? html_escape($web_settings['shipping_title']) : '' ?>" placeholder="Shipping Title" />
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="shipping_description">Description</label>
                                        <textarea name="shipping_description" class="form-control" id="shipping_description" cols="30" rows="4" placeholder="Shipping Description"><?= (isset($web_settings['shipping_description'])) ? html_escape($web_settings['shipping_description']) : '' ?></textarea>
                                    </div>

                                    <h4 class="h4 col-md-12">Returns</h4>
                                    <div class="form-group col-md-2 col-sm-4">
                                        <label for="return_mode"> Enable / Disable</label>
                                        <div class="card-body">
                                            <input type="checkbox" name="return_mode" <?= (isset($web_settings['return_mode']) && $web_settings['return_mode'] == '1') ? 'Checked' : ''  ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="return_title">Title</label>
                                        <input type="text" class="form-control" name="return_title" value="<?= (isset($web_settings['return_title'])) ? html_escape($web_settings['return_title']) : '' ?>" placeholder="Return Title" />
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="return_description">Description</label>
                                        <textarea name="return_description" class="form-control" id="return_description" cols="30" rows="4" placeholder="Return Description"><?= (isset($web_settings['return_description'])) ? html_escape($web_settings['return_description']) : '' ?></textarea>
                                    </div>

                                    <h4 class="h4 col-md-12">Support</h4>
                                    <div class="form-group col-md-2 col-sm-4">
                                        <label for="support_mode"> Enable / Disable</label>
                                        <div class="card-body">
                                            <input type="checkbox" name="support_mode" <?= (isset($web_settings['support_mode']) && $web_settings['support_mode'] == '1') ? 'Checked' : ''  ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="support_title">Title</label>
                                        <input type="text" class="form-control" name="support_title" value="<?= (isset($web_settings['support_title'])) ? html_escape($web_settings['support_title']) : '' ?>" placeholder="Support Title" />
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="shipping_description">Description</label>
                                        <textarea name="support_description" class="form-control" id="support_description" cols="30" rows="4" placeholder="Support Description"><?= (isset($web_settings['support_description'])) ? html_escape($web_settings['support_description']) : '' ?></textarea>
                                    </div>

                                    <h4 class="h4 col-md-12">Safety & Security</h4>
                                    <div class="form-group col-md-2 col-sm-4">
                                        <label for="safety_security_mode"> Enable / Disable</label>
                                        <div class="card-body">
                                            <input type="checkbox" name="safety_security_mode" <?= (isset($web_settings['safety_security_mode']) && $web_settings['safety_security_mode'] == '1') ? 'Checked' : ''  ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="safety_security_title">Title</label>
                                        <input type="text" class="form-control" name="safety_security_title" value="<?= (isset($web_settings['safety_security_title'])) ? html_escape($web_settings['safety_security_title']) : '' ?>" placeholder="Safety & Security Title" />
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="safety_security_description">Description</label>
                                        <textarea name="safety_security_description" class="form-control" id="safety_security_description" cols="30" rows="4" placeholder="Safety & Security Description"><?= (isset($web_settings['safety_security_description'])) ? html_escape($web_settings['safety_security_description']) : '' ?></textarea>
                                    </div>

                                    <!-- Header colour -->
                                    <h4 class="h4 col-md-12">Theme Classic Settings</h4>
                                    <div class="form-group col-md-2 col-sm-4">
                                        <label for="primary_color"> Primary Colour</label>
                                        <input type="text" class="coloris form-control" name="primary_color" id="primary_color" value="<?= (isset($web_settings['primary_color'])) ? html_escape($web_settings['primary_color']) : '' ?>" />
                                    </div>
                                    <div class="form-group col-md-2 col-sm-4">
                                        <label for="secondary_color"> Secondary Colour</label>
                                        <input type="text" class="coloris form-control" name="secondary_color" id="secondary_color" value="<?= (isset($web_settings['secondary_color'])) ? html_escape($web_settings['secondary_color']) : '' ?>" />
                                    </div>
                                    <div class="form-group col-md-2 col-sm-4">
                                        <label for="font_color"> Font Colour</label>
                                        <input type="text" class="coloris form-control" name="font_color" id="font_color" value="<?= (isset($web_settings['font_color'])) ? html_escape($web_settings['font_color']) : '' ?>" />
                                    </div>


                                    <div class="form-group col-md-7 pl-0">
                                        <h4>Theme Modern Settings</h4>
                                        <label for="modern_theme_color">Theme color</label>
                                        <select id="modern_theme_color" name="modern_theme_color" class="form-control col-md-5">
                                            <option value="default">default</option>
                                            <option value="aqua">Aqua</option>
                                            <option value="fuchsia">Fuchsia</option>
                                            <option value="grape">Grape</option>
                                            <option value="green">Green</option>
                                            <option value="leaf">Leaf</option>
                                            <option value="navy">Navy</option>
                                            <option value="pink">Pink</option>
                                            <option value="purple">Purple</option>
                                            <option value="red">Red</option>
                                            <option value="sky">Sky</option>
                                            <option value="violet">Violet</option>
                                        </select>
                                    </div>


                                </div>
                            </div>
                        </div>

                        <div class="card attribute-card">
                            <div class="card-body pt-3">
                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="error_box">
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn">Update Settings</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
</div>

<style>
    .admin-web-settings-page .text-primary-theme { color: var(--color-orange); }

    .admin-web-settings-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); margin-bottom: 1.5rem; }
    .admin-web-settings-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-web-settings-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-web-settings-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-web-settings-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-web-settings-page .btn-success:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }
</style>
