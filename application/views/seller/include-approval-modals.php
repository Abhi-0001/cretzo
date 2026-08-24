<?php
/**
 * Seller admin-approval modals.
 *
 * Rendered by any seller page that needs to talk about the approval gate. The caller sets
 * $approval_modal_mode:
 *   'dashboard' - the nag / pending / approved popups shown on the seller dashboard
 *   'checkout'  - the blocking popup raised when an unapproved seller clicks Proceed to Pay
 *
 * $seller_approval comes from seller_approval_state() and is the single source of truth for
 * which stage the seller is at; the caller must pass it (or accept the safe "incomplete"
 * default below, which nags rather than silently letting the seller through).
 */
$approval_modal_mode  = isset($approval_modal_mode) ? $approval_modal_mode : 'dashboard';
$approval_state       = isset($seller_approval) && is_array($seller_approval) ? $seller_approval : [];
$approval_stage       = isset($approval_state['stage']) ? $approval_state['stage'] : 'incomplete';
$approval_requested   = !empty($approval_state['requested_at']) ? $approval_state['requested_at'] : null;
$show_approved_popup  = !empty($approval_state['show_approval_popup']);
$approval_profile_url = base_url('seller/home/profile?section=admin');
?>
<link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/cretzo/seller-approval-modal.css')) ?>">

<?php if ($approval_stage !== 'approved') : ?>
    <div class="modal fade cz-approve-modal" id="czApprovalGateModal" tabindex="-1" role="dialog"
         aria-labelledby="czApprovalGateTitle">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

                <?php if ($approval_stage === 'incomplete') : ?>
                    <div class="cz-approve-hero">
                        <span class="cz-approve-eyebrow">Action required</span>
                        <div class="cz-approve-badge"><i class="fas fa-id-card-alt"></i></div>
                        <h4 id="czApprovalGateTitle">Complete your profile to start selling</h4>
                        <p>Your seller account is created. Fill in your business, pickup and bank details, then submit them for admin approval &mdash; product listing unlocks once your account is approved.</p>
                    </div>
                    <div class="cz-approve-body">
                        <ul class="cz-approve-steps">
                            <li class="is-done">
                                <span class="cz-step-icon"><i class="fas fa-check"></i></span>
                                <div>
                                    <h6>Account created</h6>
                                    <p>You can explore the seller panel while your details are being completed.</p>
                                </div>
                            </li>
                            <li>
                                <span class="cz-step-icon"><i class="fas fa-edit"></i></span>
                                <div>
                                    <h6>Fill your profile details</h6>
                                    <p>Personal details, store and pickup address, PAN / GSTIN and bank account.</p>
                                </div>
                            </li>
                            <li>
                                <span class="cz-step-icon"><i class="fas fa-paper-plane"></i></span>
                                <div>
                                    <h6>Submit for admin approval</h6>
                                    <p>Send your details to the Cretzo team for verification from the profile page.</p>
                                </div>
                            </li>
                            <li class="is-locked">
                                <span class="cz-step-icon"><i class="fas fa-lock"></i></span>
                                <div>
                                    <h6>List your products &amp; subscribe</h6>
                                    <p>Unlocked after the admin approves your account.</p>
                                </div>
                            </li>
                        </ul>
                        <p class="cz-approve-note">
                            <i class="fas fa-info-circle"></i>
                            Keep your PAN, GSTIN and a bank account proof handy &mdash; complete, accurate details are approved much faster.
                        </p>
                    </div>
                    <div class="modal-footer cz-approve-footer">
                        <a href="<?= $approval_profile_url ?>" class="btn btn-cz-primary">
                            <i class="fas fa-arrow-right mr-2"></i>Complete profile &amp; submit
                        </a>
                        <button type="button" class="btn btn-cz-ghost" data-dismiss="modal">Not now</button>
                        <p class="cz-approve-meta">
                            <i class="fas fa-exclamation-triangle"></i>
                            This reminder will keep appearing until your details are submitted for approval.
                        </p>
                    </div>

                <?php else : ?>
                    <div class="cz-approve-hero is-pending">
                        <span class="cz-approve-eyebrow">Under review</span>
                        <div class="cz-approve-badge"><i class="fas fa-hourglass-half"></i></div>
                        <h4 id="czApprovalGateTitle">Your details are with the admin team</h4>
                        <p>Thank you &mdash; your profile has been submitted for verification. Once the admin approves your account you can list products and choose a subscription plan.</p>
                    </div>
                    <div class="cz-approve-body">
                        <ul class="cz-approve-steps">
                            <li class="is-done">
                                <span class="cz-step-icon"><i class="fas fa-check"></i></span>
                                <div>
                                    <h6>Profile details submitted</h6>
                                    <p><?= $approval_requested ? 'Sent for review on ' . html_escape(date('d M Y, h:i A', strtotime($approval_requested))) . '.' : 'Sent to the admin team for review.' ?></p>
                                </div>
                            </li>
                            <li>
                                <span class="cz-step-icon"><i class="fas fa-user-shield"></i></span>
                                <div>
                                    <h6>Admin verification in progress</h6>
                                    <p>Our team is checking your business, tax and bank details.</p>
                                </div>
                            </li>
                            <li class="is-locked">
                                <span class="cz-step-icon"><i class="fas fa-lock"></i></span>
                                <div>
                                    <h6>Product listing &amp; subscription</h6>
                                    <p>These unlock automatically the moment your account is approved.</p>
                                </div>
                            </li>
                        </ul>
                        <p class="cz-approve-note is-pending">
                            <i class="fas fa-bell"></i>
                            You will be notified by email as soon as a decision is made. You can keep updating your profile in the meantime.
                        </p>
                    </div>
                    <div class="modal-footer cz-approve-footer">
                        <button type="button" class="btn btn-cz-primary" data-dismiss="modal">
                            <i class="fas fa-check mr-2"></i>Got it
                        </button>
                        <a href="<?= $approval_profile_url ?>" class="btn btn-cz-ghost">Review my details</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($approval_modal_mode === 'dashboard' && $show_approved_popup) : ?>
    <div class="modal fade cz-approve-modal" id="czApprovalSuccessModal" tabindex="-1" role="dialog" aria-labelledby="czApprovalSuccessTitle">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="cz-approve-hero is-approved">
                    <span class="cz-approve-eyebrow">Account approved</span>
                    <div class="cz-approve-badge"><i class="fas fa-award"></i></div>
                    <h4 id="czApprovalSuccessTitle">Congratulations &mdash; you are verified</h4>
                    <p>The admin team has approved your seller account. Your store is ready: you can list products, pick a subscription plan and start receiving orders.</p>
                </div>
                <div class="cz-approve-body">
                    <ul class="cz-approve-steps">
                        <li class="is-done">
                            <span class="cz-step-icon"><i class="fas fa-box-open"></i></span>
                            <div>
                                <h6>Add your products</h6>
                                <p>Create listings one by one, or use bulk upload for a large catalogue.</p>
                            </div>
                        </li>
                        <li class="is-done">
                            <span class="cz-step-icon"><i class="fas fa-crown"></i></span>
                            <div>
                                <h6>Choose a subscription plan</h6>
                                <p>Your plan decides your listing limit and commission slabs.</p>
                            </div>
                        </li>
                        <li class="is-done">
                            <span class="cz-step-icon"><i class="fas fa-shipping-fast"></i></span>
                            <div>
                                <h6>Set up your pickup location</h6>
                                <p>Required before your first order can be shipped.</p>
                            </div>
                        </li>
                    </ul>
                    <p class="cz-approve-note is-approved">
                        <i class="fas fa-check-circle"></i>
                        This confirmation is shown only once. Your approval status is always visible on the Profile page.
                    </p>
                </div>
                <div class="modal-footer cz-approve-footer">
                    <a href="<?= base_url('seller/product') ?>" class="btn btn-cz-primary">
                        <i class="fas fa-plus mr-2"></i>List my first product
                    </a>
                    <a href="<?= base_url('seller/subscription/manage_subscriptions') ?>" class="btn btn-cz-ghost">View plans</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    (function () {
        var stage = <?= json_encode($approval_stage) ?>;
        var mode = <?= json_encode($approval_modal_mode) ?>;
        var showApproved = <?= $show_approved_popup ? 'true' : 'false' ?>;

        function ack() {
            var data = {};
            if (typeof csrfName !== 'undefined') data[csrfName] = csrfHash;
            fetch(base_url + 'seller/home/acknowledge_approval_popup', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data)
            }).then(function (r) { return r.json(); }).then(function (res) {
                if (res && res.csrfName && res.csrfHash) { csrfName = res.csrfName; csrfHash = res.csrfHash; }
            }).catch(function () { /* worst case the seller sees the confirmation once more */ });
        }

        $(function () {
            if (mode === 'dashboard') {
                if (showApproved) {
                    // Acknowledged on open, not on close: a seller who navigates away without
                    // dismissing the modal has still seen it, and re-showing it later would
                    // make a one-time message look like a recurring alert.
                    $('#czApprovalSuccessModal').on('shown.bs.modal', ack).modal('show');
                } else if (stage !== 'approved') {
                    // The pending reassurance is once per session; the "complete your profile"
                    // nag is deliberately every load, because it is the seller's own blocker.
                    var oncePerSession = stage === 'pending';
                    var seen = false;
                    try { seen = sessionStorage.getItem('czApprovalPendingSeen') === '1'; } catch (e) {}
                    if (!oncePerSession || !seen) {
                        if (oncePerSession) {
                            try { sessionStorage.setItem('czApprovalPendingSeen', '1'); } catch (e) {}
                        }
                        $('#czApprovalGateModal').modal('show');
                    }
                }
            }
        });

        // Exposed so the subscription checkout can raise the same popup on click, instead of
        // duplicating the markup and copy on that page.
        window.czShowApprovalGate = function () {
            var $modal = $('#czApprovalGateModal');
            if (!$modal.length) return false;
            $modal.modal('show');
            return true;
        };
    })();
</script>
