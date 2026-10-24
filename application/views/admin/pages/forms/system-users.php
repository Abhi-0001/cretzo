<?php
/*
 * Add / Edit System User.
 *
 * Access is picked per SECTION (None / View / Edit / Full) instead of ticking ~140 individual
 * create/read/update/delete switches. The page still posts permissions[module][action] -
 * system-user-form JS expands each section choice into those fields - so the save endpoint,
 * the model and has_permissions() are untouched.
 */
$is_edit = isset($fetched_data[0]['id']);
$u = $is_edit ? $fetched_data[0] : [];
$stored_perms = $is_edit ? (json_decode((string) $u['permissions'], true) ?: []) : [];
$is_super_admin = !empty($is_super_admin);

$sections = [
    ['key' => 'orders',    'title' => 'Orders & Customers', 'icon' => 'fa-shopping-bag', 'modules' => ['orders', 'return_request', 'customers', 'payment_request', 'transactions', 'fund_transfer', 'support_tickets', 'chat', 'delivery_boy']],
    ['key' => 'catalog',   'title' => 'Products & Catalogue', 'icon' => 'fa-box-open', 'modules' => ['product', 'product_order', 'categories', 'category_order', 'brands', 'media', 'tax', 'attribute', 'attribute_set', 'attribute_value', 'manage_stock']],
    ['key' => 'marketing', 'title' => 'Storefront & Marketing', 'icon' => 'fa-bullhorn', 'modules' => ['home_slider_images', 'new_offer_images', 'featured_section', 'promo_code', 'referral', 'blogs', 'blog_categories', 'faq', 'custom_notifications', 'send_notification', 'custom_sms']],
    ['key' => 'sellers',   'title' => 'Sellers', 'icon' => 'fa-store', 'modules' => ['seller', 'subscription', 'pickup_location']],
    ['key' => 'reports',   'title' => 'Reports', 'icon' => 'fa-chart-line', 'modules' => ['sales_report', 'sales_inventory']],
    ['key' => 'content',   'title' => 'Content Pages', 'icon' => 'fa-file-alt', 'modules' => ['contact_us', 'about_us', 'privacy_policy', 'shipping_policy', 'return_policy', 'admin_privacy_policy', 'delivery_boy_privacy_policy', 'seller_privacy_policy']],
    ['key' => 'settings',  'title' => 'Settings & System', 'icon' => 'fa-cog', 'modules' => ['settings', 'email_settings', 'payment_settings', 'shipping_settings', 'notification_setting', 'sms-gateway-settings', 'authentication_settings', 'time_slots', 'area', 'city', 'zipcodes', 'client_api_keys', 'system_update', 'purchase_code']],
];
// Any module registered in config/eshop.php but not listed above still gets a home.
$placed = ['profile']; // granted to everyone automatically (see system-user-form.js)
foreach ($sections as $sec) {
    $placed = array_merge($placed, $sec['modules']);
}
$leftover = array_values(array_diff(array_keys($system_modules), $placed));
if ($leftover) {
    $sections[] = ['key' => 'other', 'title' => 'Other', 'icon' => 'fa-ellipsis-h', 'modules' => $leftover];
}

$js_sections = [];
foreach ($sections as $sec) {
    $mods = [];
    foreach ($sec['modules'] as $m) {
        if (isset($system_modules[$m])) {
            $mods[$m] = array_values($system_modules[$m]);
        }
    }
    if (!$mods) {
        continue;
    }
    $js_sections[$sec['key']] = $mods;
}

$role_cards = [
    '0' => ['Super Admin', 'Full control of everything, including other staff accounts.', 'fa-crown'],
    '1' => ['Admin', 'Runs the store day to day. Pick what they can reach.', 'fa-user-shield'],
    '2' => ['Editor', 'Manages products and content.', 'fa-pen'],
    '3' => ['Supporter', 'Handles orders and customers.', 'fa-headset'],
];
$current_role = $is_edit ? (string) $u['role'] : '';
?>
<div class="content-wrapper admin-system-user-form-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-user-shield mr-2 text-primary-theme"></i><?= $is_edit ? 'Edit System User' : 'Add System User' ?></h4>
                    <p class="text-muted mb-0 small">Someone who signs in to this admin panel, and what they are allowed to do.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/system_users') ?>">System Users</a></li>
                        <li class="breadcrumb-item active"><?= $is_edit ? 'Edit' : 'Add' ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <form class="form-submit-event" action="<?= base_url('admin/system_users/update_system_user'); ?>" method="POST" id="add_product_form" autocomplete="off" novalidate>
                <?php if ($is_edit) { ?>
                    <input type="hidden" name="edit_system_user" value="<?= html_escape($u['id']) ?>">
                <?php } ?>

                <div class="card su-card">
                    <div class="card-header su-card-header"><span class="su-step">1</span> Account details</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="username">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" id="username" value="<?= html_escape($u['username'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="mobile">Mobile <span class="text-danger">*</span></label>
                                <input type="tel" inputmode="numeric" class="form-control" name="mobile" id="mobile" value="<?= html_escape($u['mobile'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" id="email" value="<?= html_escape($u['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 form-group mb-md-0">
                                <label for="password">Password <?= $is_edit ? '' : '<span class="text-danger">*</span>' ?></label>
                                <input type="password" class="form-control" name="password" id="password" autocomplete="new-password">
                                <?php if ($is_edit) { ?><small class="text-muted">Leave blank to keep the current password.</small><?php } ?>
                            </div>
                            <div class="col-md-4 form-group mb-0">
                                <label for="confirm_password">Confirm password <?= $is_edit ? '' : '<span class="text-danger">*</span>' ?></label>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card su-card">
                    <div class="card-header su-card-header"><span class="su-step">2</span> Role</div>
                    <div class="card-body">
                        <input type="hidden" name="role" id="su_role" value="<?= html_escape($current_role) ?>">
                        <div class="row su-roles">
                            <?php foreach ($role_cards as $rk => $rc) {
                                if (!$is_super_admin && !in_array((string) $rk, ['2', '3'], true)) {
                                    continue; // non-owners may only create Editor / Supporter
                                } ?>
                                <div class="col-md-3 col-6 mb-2">
                                    <button type="button" class="su-role <?= $current_role === (string) $rk ? 'active' : '' ?>" data-role="<?= $rk ?>">
                                        <i class="fas <?= $rc[2] ?>"></i>
                                        <b><?= $rc[0] ?></b>
                                        <span><?= $rc[1] ?></span>
                                    </button>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="card su-card" id="su_access_card" style="display:none">
                    <div class="card-header su-card-header d-flex flex-wrap align-items-center justify-content-between">
                        <span><span class="su-step">3</span> What can they do?</span>
                        <span class="small text-muted font-weight-normal">None = hidden &middot; View = look only &middot; Edit = add &amp; change &middot; Full = also delete</span>
                    </div>
                    <div class="card-body pt-2">
                        <?php foreach ($sections as $sec) {
                            if (!isset($js_sections[$sec['key']])) {
                                continue;
                            }
                            $names = array_map(function ($m) {
                                return ucwords(str_replace(['_', '-'], ' ', $m));
                            }, array_keys($js_sections[$sec['key']]));
                        ?>
                            <div class="su-section" data-section="<?= $sec['key'] ?>">
                                <div class="su-section-info">
                                    <b><i class="fas <?= $sec['icon'] ?> mr-2"></i><?= $sec['title'] ?></b>
                                    <small class="d-block text-muted"><?= html_escape(implode(', ', $names)) ?></small>
                                </div>
                                <div class="su-levels" role="group">
                                    <button type="button" class="su-level" data-level="none">None</button>
                                    <button type="button" class="su-level" data-level="view">View</button>
                                    <button type="button" class="su-level" data-level="edit">Edit</button>
                                    <button type="button" class="su-level" data-level="full">Full</button>
                                    <button type="button" class="su-level su-custom" data-level="custom" style="display:none" title="Mixed permissions saved earlier - kept until you pick a level">Custom</button>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div id="su_perm_inputs"></div>

                <div class="su-actions">
                    <div id="error_box"></div>
                    <a href="<?= base_url('admin/system_users') ?>" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-success px-4" id="submit_btn"><?= $is_edit ? 'Save changes' : 'Add user' ?></button>
                </div>
            </form>
        </div>
    </section>
</div>

<div class="modal fade" id="su_approval_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-shield-alt mr-2"></i>Two-step verification</h5>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Approval is needed to add or change a system user. Enter the code emailed to
                    <b id="su_approver_email"></b> and the SMS code sent to <b id="su_approver_mobile"></b>.
                </p>
                <div class="form-group">
                    <label for="su_email_otp">Email code</label>
                    <input type="text" class="form-control" id="su_email_otp" inputmode="numeric" maxlength="6" autocomplete="one-time-code">
                </div>
                <div class="form-group">
                    <label for="su_sms_otp">SMS code</label>
                    <input type="text" class="form-control" id="su_sms_otp" inputmode="numeric" maxlength="6" autocomplete="off">
                </div>
                <div id="su_approval_msg" class="small"></div>
                <div id="su_recaptcha"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link mr-auto" id="su_resend_btn">Resend codes</button>
                <button type="button" class="btn btn-secondary" id="su_cancel_btn">Cancel</button>
                <button type="button" class="btn btn-success" id="su_verify_btn">Verify &amp; save</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.SU_PERM = {
        sections: <?= json_encode($js_sections) ?>,
        stored: <?= json_encode($stored_perms ?: new stdClass()) ?>,
        isEdit: <?= $is_edit ? 'true' : 'false' ?>,
        // Starting levels when a role is picked. Admin is unrestricted by default; Editor and
        // Supporter get the sections their job needs and nothing else.
        presets: {
            '1': {orders: 'full', catalog: 'full', marketing: 'full', sellers: 'full', reports: 'full', content: 'full', settings: 'full'},
            '2': {orders: 'view', catalog: 'edit', marketing: 'edit', sellers: 'none', reports: 'none', content: 'edit', settings: 'none'},
            '3': {orders: 'edit', catalog: 'view', marketing: 'none', sellers: 'view', reports: 'none', content: 'none', settings: 'none'}
        }
    };
</script>
<script src="<?= base_url('assets/admin/js/cretzo/system-user-form.js') ?>?v=<?= @filemtime(FCPATH . 'assets/admin/js/cretzo/system-user-form.js') ?: time() ?>"></script>
<script src="<?= base_url('assets/admin/js/cretzo/system-user-approval.js') ?>?v=<?= @filemtime(FCPATH . 'assets/admin/js/cretzo/system-user-approval.js') ?: time() ?>"></script>

<style>
    .admin-system-user-form-page .text-primary-theme { color: var(--color-orange); }
    .admin-system-user-form-page .su-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); margin-bottom: 16px; }
    .admin-system-user-form-page .su-card-header { background: #fff; border-bottom: 1px solid rgba(0,0,0,0.06); border-radius: 10px 10px 0 0; font-weight: 600; font-size: 15px; }
    .admin-system-user-form-page .su-step {
        display: inline-flex; width: 22px; height: 22px; border-radius: 50%; align-items: center; justify-content: center;
        background: var(--color-orange); color: #fff; font-size: 12px; margin-right: 8px;
    }
    .admin-system-user-form-page label { font-weight: 600; font-size: 13px; margin-bottom: 4px; }

    .admin-system-user-form-page .su-role {
        width: 100%; height: 100%; text-align: left; background: #fff; border: 1.5px solid rgba(0,0,0,0.12);
        border-radius: 10px; padding: 12px 14px; cursor: pointer; display: block; transition: border-color .15s, background .15s;
    }
    .admin-system-user-form-page .su-role i { color: var(--color-orange); font-size: 18px; margin-bottom: 6px; display: block; }
    .admin-system-user-form-page .su-role b { display: block; font-size: 14px; color: #222; }
    .admin-system-user-form-page .su-role span { display: block; font-size: 12px; color: #777; margin-top: 2px; line-height: 1.35; }
    .admin-system-user-form-page .su-role:hover { border-color: var(--color-orange); }
    .admin-system-user-form-page .su-role.active { border-color: var(--color-orange); background: var(--color-orange-light, #fff4ec); box-shadow: 0 0 0 1px var(--color-orange); }

    .admin-system-user-form-page .su-section {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px;
        padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .admin-system-user-form-page .su-section:last-child { border-bottom: none; }
    .admin-system-user-form-page .su-section-info { flex: 1 1 280px; min-width: 0; }
    .admin-system-user-form-page .su-section-info small { line-height: 1.4; }
    .admin-system-user-form-page .su-levels { display: inline-flex; border: 1px solid rgba(0,0,0,0.15); border-radius: 8px; overflow: hidden; flex: none; }
    .admin-system-user-form-page .su-level {
        background: #fff; border: none; border-left: 1px solid rgba(0,0,0,0.1); padding: 7px 16px; font-size: 13px; font-weight: 600; color: #666; cursor: pointer;
    }
    .admin-system-user-form-page .su-level:first-child { border-left: none; }
    .admin-system-user-form-page .su-level:hover { background: #f6f6f6; }
    .admin-system-user-form-page .su-level.active[data-level="none"] { background: #e9ecef; color: #333; }
    .admin-system-user-form-page .su-level.active[data-level="view"] { background: #17a2b8; color: #fff; }
    .admin-system-user-form-page .su-level.active[data-level="edit"] { background: #28a745; color: #fff; }
    .admin-system-user-form-page .su-level.active[data-level="full"] { background: var(--color-orange); color: #fff; }
    .admin-system-user-form-page .su-level.active[data-level="custom"] { background: #6c757d; color: #fff; }

    .admin-system-user-form-page .su-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; flex-wrap: wrap; padding-bottom: 20px; }
    .admin-system-user-form-page .su-actions #error_box { margin-right: auto; }
</style>
