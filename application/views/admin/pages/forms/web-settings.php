<?php
/**
 * Admin > Web Settings
 *
 * Only settings that the live storefront actually reads are exposed here. The old
 * "Feature Section" block (shipping / returns / support / safety & security - each with a
 * toggle, title and description) was removed: none of those twelve fields are referenced
 * anywhere under application/views/front-end, so editing them changed nothing on the site.
 * "Copyright Details" went the same way - the footer never rendered it.
 *
 * Structural hooks that must not be renamed (plugins bind to them):
 *  - form.form-submit-event + #error_box + #submit_btn : admin AJAX submit handler
 *  - a.uploadFile[data-input] and a sibling .image-upload-section inside the SAME .form-group
 *    (custom.js does .closest('.form-group').find('.image-upload-section'))
 *  - input.coloris                                     : colour picker
 *  - input[data-bootstrap-switch]                      : on/off switch
 */
$ws = isset($web_settings) && is_array($web_settings) ? $web_settings : [];
$val = function ($key) use ($ws) {
    return isset($ws[$key]) ? html_escape($ws[$key]) : '';
};
// Palette files that really exist under assets/front_end/<theme>/css/colors/. The old list
// offered "default" (no such stylesheet - a guaranteed 404) and omitted orange/blue, and it
// never marked the saved value as selected, so the dropdown always displayed the first entry
// regardless of what was stored.
$modern_palettes = ['orange', 'aqua', 'blue', 'fuchsia', 'grape', 'green', 'leaf', 'navy', 'pink', 'purple', 'red', 'sky', 'violet'];
$modern_selected = (isset($ws['modern_theme_color']) && in_array($ws['modern_theme_color'], $modern_palettes, true)) ? $ws['modern_theme_color'] : 'orange';
?>
<div class="content-wrapper aws-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-3 align-items-center">
                <div class="col-md-7">
                    <div class="aws-head">
                        <span class="aws-head__icon"><i class="fas fa-globe"></i></span>
                        <div>
                            <h4 class="aws-head__title">Website Settings</h4>
                            <p class="aws-head__sub">Site identity, branding, SEO, contact details and storefront theme.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <ol class="breadcrumb float-md-right bg-transparent mb-0">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Website Settings</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="aws-layout">

                <nav class="aws-nav">
                    <a href="#aws-identity"><i class="fas fa-id-card"></i> Site Identity</a>
                    <a href="#aws-branding"><i class="fas fa-image"></i> Logo &amp; Favicon</a>
                    <a href="#aws-theme"><i class="fas fa-palette"></i> Theme Colours</a>
                    <a href="#aws-seo"><i class="fas fa-search"></i> SEO</a>
                    <a href="#aws-contact"><i class="fas fa-map-marker-alt"></i> Contact &amp; Map</a>
                    <a href="#aws-social"><i class="fas fa-share-alt"></i> Social Links</a>
                    <a href="#aws-app"><i class="fas fa-mobile-alt"></i> App Promo</a>
                </nav>

                <form class="form-submit-event" action="<?= base_url('admin/setting/update_web_settings') ?>" method="POST" id="system_setting_form" enctype="multipart/form-data">

                    <!-- ============ Site identity ============ -->
                    <div class="aws-card" id="aws-identity">
                        <div class="aws-card__head">
                            <span class="aws-card__icon"><i class="fas fa-id-card"></i></span>
                            <div>
                                <h5 class="aws-card__title">Site Identity</h5>
                                <p class="aws-card__note">Shown in the browser tab, the footer and on the contact page.</p>
                            </div>
                        </div>
                        <div class="aws-card__body">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="site_title">Site Title <i class="aws-req">*</i></label>
                                    <input type="text" class="form-control" id="site_title" name="site_title" value="<?= $val('site_title') ?>" placeholder="Cretzo" />
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="support_number">Support Number <i class="aws-req">*</i></label>
                                    <input type="text" class="form-control" id="support_number" name="support_number" value="<?= $val('support_number') ?>" placeholder="Customer support mobile number" />
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="support_email">Support Email <i class="aws-req">*</i></label>
                                    <input type="email" class="form-control" id="support_email" name="support_email" value="<?= $val('support_email') ?>" placeholder="support@example.com" />
                                </div>
                                <div class="form-group col-md-12">
                                    <label for="app_short_description">Short Description <small>Appears under the logo in the storefront footer.</small></label>
                                    <textarea name="app_short_description" id="app_short_description" class="form-control" rows="3"><?= $val('app_short_description') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ Branding ============ -->
                    <div class="aws-card" id="aws-branding">
                        <div class="aws-card__head">
                            <span class="aws-card__icon"><i class="fas fa-image"></i></span>
                            <div>
                                <h5 class="aws-card__title">Logo &amp; Favicon</h5>
                                <p class="aws-card__note">Upload a new file only when you actually want to replace the current one.</p>
                            </div>
                        </div>
                        <div class="aws-card__body">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label>Logo <i class="aws-req">*</i></label>
                                    <div class="aws-media">
                                        <a class="uploadFile img btn btn-primary text-white btn-sm" data-input="logo" data-isremovable="0" data-is-multiple-uploads-allowed="0" data-toggle="modal" data-target="#media-upload-modal"><i class="fa fa-upload"></i> Upload Logo</a>
                                        <div class="container-fluid row image-upload-section">
                                            <?php if (!empty($logo)) { ?>
                                                <div class="col-md-3 col-sm-12 text-center image">
                                                    <div class="upload-media-div"><img class="img-fluid mb-2" src="<?= html_escape(BASE_URL() . $logo) ?>" alt="Image Not Found"></div>
                                                    <input type="hidden" name="logo" id="logo" value="<?= html_escape($logo) ?>">
                                                </div>
                                            <?php } else { ?>
                                                <div class="col-md-3 col-sm-12 text-center image d-none"></div>
                                            <?php } ?>
                                        </div>
                                        <small class="aws-media__hint">Recommended: larger than 120 &times; 120 and smaller than 150 &times; 150 pixels.</small>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Favicon <i class="aws-req">*</i></label>
                                    <div class="aws-media">
                                        <a class="uploadFile img btn btn-primary text-white btn-sm" data-input="favicon" data-isremovable="0" data-is-multiple-uploads-allowed="0" data-toggle="modal" data-target="#media-upload-modal"><i class="fa fa-upload"></i> Upload Favicon</a>
                                        <div class="container-fluid row image-upload-section">
                                            <?php if (!empty($favicon)) { ?>
                                                <div class="col-md-3 col-sm-12 text-center image">
                                                    <div class="upload-media-div"><img class="img-fluid mb-2" src="<?= html_escape(BASE_URL() . $favicon) ?>" alt="Image Not Found"></div>
                                                    <input type="hidden" name="favicon" id="favicon" value="<?= html_escape($favicon) ?>">
                                                </div>
                                            <?php } else { ?>
                                                <div class="col-md-3 col-sm-12 text-center image d-none"></div>
                                            <?php } ?>
                                        </div>
                                        <small class="aws-media__hint">A square image, 32 &times; 32 pixels or larger.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ Theme colours ============ -->
                    <div class="aws-card" id="aws-theme">
                        <div class="aws-card__head">
                            <span class="aws-card__icon"><i class="fas fa-palette"></i></span>
                            <div>
                                <h5 class="aws-card__title">Storefront Theme Colours</h5>
                                <p class="aws-card__note">The three colours drive the storefront CSS variables; the palette swaps the accent stylesheet.</p>
                            </div>
                        </div>
                        <div class="aws-card__body">
                            <div class="aws-swatch-grid mb-3">
                                <div class="form-group aws-swatch">
                                    <label for="primary_color">Primary Colour</label>
                                    <input type="text" class="coloris form-control" name="primary_color" id="primary_color" value="<?= $val('primary_color') ?>" />
                                </div>
                                <div class="form-group aws-swatch">
                                    <label for="secondary_color">Secondary Colour</label>
                                    <input type="text" class="coloris form-control" name="secondary_color" id="secondary_color" value="<?= $val('secondary_color') ?>" />
                                </div>
                                <div class="form-group aws-swatch">
                                    <label for="font_color">Font Colour</label>
                                    <input type="text" class="coloris form-control" name="font_color" id="font_color" value="<?= $val('font_color') ?>" />
                                </div>
                                <div class="form-group aws-swatch">
                                    <label for="modern_theme_color">Accent Palette</label>
                                    <select id="modern_theme_color" name="modern_theme_color" class="form-control">
                                        <?php foreach ($modern_palettes as $palette) { ?>
                                            <option value="<?= $palette ?>" <?= ($palette === $modern_selected) ? 'selected' : '' ?>><?= ucfirst($palette) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ SEO ============ -->
                    <div class="aws-card" id="aws-seo">
                        <div class="aws-card__head">
                            <span class="aws-card__icon"><i class="fas fa-search"></i></span>
                            <div>
                                <h5 class="aws-card__title">SEO Defaults</h5>
                                <p class="aws-card__note">Used on pages that do not define their own meta tags.</p>
                            </div>
                        </div>
                        <div class="aws-card__body">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="meta_keywords">Meta Keywords</label>
                                    <textarea name="meta_keywords" id="meta_keywords" class="form-control" rows="4" placeholder="comma, separated, keywords"><?= (isset($ws['meta_keywords'])) ? html_escape(str_replace(array("\n\r", "\n", "\r", "\\"), "", $ws['meta_keywords'])) : '' ?></textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="meta_description">Meta Description</label>
                                    <textarea name="meta_description" id="meta_description" class="form-control" rows="4" placeholder="A short summary of the store."><?= $val('meta_description') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ Contact & map ============ -->
                    <div class="aws-card" id="aws-contact">
                        <div class="aws-card__head">
                            <span class="aws-card__icon"><i class="fas fa-map-marker-alt"></i></span>
                            <div>
                                <h5 class="aws-card__title">Contact &amp; Map</h5>
                                <p class="aws-card__note">Rendered on the storefront Contact Us page.</p>
                            </div>
                        </div>
                        <div class="aws-card__body">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="address">Address</label>
                                    <textarea name="address" id="address" class="form-control" rows="3"><?= $val('address') ?></textarea>
                                </div>
                                <div class="form-group col-md-12">
                                    <label for="map_iframe">Map Embed Code <small>Paste the full &lt;iframe&gt; snippet from Google Maps &rarr; Share &rarr; Embed a map.</small></label>
                                    <textarea name="map_iframe" id="map_iframe" class="form-control" rows="4" placeholder='&lt;iframe src="https://www.google.com/maps/embed?..."&gt;&lt;/iframe&gt;'><?= $val('map_iframe') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ Social ============ -->
                    <div class="aws-card" id="aws-social">
                        <div class="aws-card__head">
                            <span class="aws-card__icon"><i class="fas fa-share-alt"></i></span>
                            <div>
                                <h5 class="aws-card__title">Social Links</h5>
                                <p class="aws-card__note">Leave a field empty to hide that icon in the footer.</p>
                            </div>
                        </div>
                        <div class="aws-card__body">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="facebook_link"><i class="fab fa-facebook-f mr-1"></i> Facebook</label>
                                    <input type="text" class="form-control" id="facebook_link" name="facebook_link" value="<?= $val('facebook_link') ?>" placeholder="https://facebook.com/..." />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="instagram_link"><i class="fab fa-instagram mr-1"></i> Instagram</label>
                                    <input type="text" class="form-control" id="instagram_link" name="instagram_link" value="<?= $val('instagram_link') ?>" placeholder="https://instagram.com/..." />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="twitter_link"><i class="fab fa-twitter mr-1"></i> Twitter / X</label>
                                    <input type="text" class="form-control" id="twitter_link" name="twitter_link" value="<?= $val('twitter_link') ?>" placeholder="https://x.com/..." />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="youtube_link"><i class="fab fa-youtube mr-1"></i> YouTube</label>
                                    <input type="text" class="form-control" id="youtube_link" name="youtube_link" value="<?= $val('youtube_link') ?>" placeholder="https://youtube.com/..." />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ App promo ============ -->
                    <div class="aws-card" id="aws-app">
                        <div class="aws-card__head">
                            <span class="aws-card__icon"><i class="fas fa-mobile-alt"></i></span>
                            <div>
                                <h5 class="aws-card__title">Mobile App Promo</h5>
                                <p class="aws-card__note">Optional banner at the bottom of the storefront home page.</p>
                            </div>
                        </div>
                        <div class="aws-card__body">
                            <div class="aws-toggle">
                                <div>
                                    <strong>Show the app download banner</strong>
                                    <span>Turn this off if the apps are not published yet - the whole section disappears from the home page.</span>
                                </div>
                                <input type="checkbox" name="app_download_section" <?= (isset($ws['app_download_section']) && $ws['app_download_section'] == '1') ? 'checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="app_download_section_title">Title</label>
                                    <input type="text" class="form-control" id="app_download_section_title" name="app_download_section_title" value="<?= $val('app_download_section_title') ?>" placeholder="Get the app" />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="app_download_section_tagline">Tagline</label>
                                    <input type="text" class="form-control" id="app_download_section_tagline" name="app_download_section_tagline" value="<?= $val('app_download_section_tagline') ?>" placeholder="Shop faster on mobile" />
                                </div>
                                <div class="form-group col-md-12">
                                    <label for="app_download_section_short_description">Short Description</label>
                                    <textarea name="app_download_section_short_description" id="app_download_section_short_description" class="form-control" rows="3"><?= $val('app_download_section_short_description') ?></textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="app_download_section_playstore_url">Play Store URL</label>
                                    <input type="text" class="form-control" id="app_download_section_playstore_url" name="app_download_section_playstore_url" value="<?= $val('app_download_section_playstore_url') ?>" placeholder="https://play.google.com/store/apps/details?id=..." />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="app_download_section_appstore_url">App Store URL</label>
                                    <input type="text" class="form-control" id="app_download_section_appstore_url" name="app_download_section_appstore_url" value="<?= $val('app_download_section_appstore_url') ?>" placeholder="https://apps.apple.com/app/..." />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="aws-actions">
                        <div id="error_box"></div>
                        <button type="reset" class="btn btn-outline-secondary btn-reset">Reset</button>
                        <button type="submit" class="btn btn-save" id="submit_btn">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<link rel="stylesheet" href="<?= base_url('assets/admin/css/cretzo/admin-web-settings.css') ?>?v=<?= @filemtime(FCPATH . 'assets/admin/css/cretzo/admin-web-settings.css') ?: time() ?>">
