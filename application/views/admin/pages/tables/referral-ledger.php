<?php
/**
 * The attribution ledger: who referred whom.
 *
 * Shows every referral, including ones that have earned nothing and ones whose
 * programme is switched off - "who invited this customer" has an answer whether
 * or not money followed, and that answer is what support needs when somebody
 * writes in about a missing reward.
 */
$czr_title = 'Referral Ledger';
$czr_crumb = 'Ledger';
$czr_sub = 'Every referral ever recorded, whether or not it earned anything. Search by name, mobile or code to answer &ldquo;who invited this customer&rdquo;.';
?>
<div class="content-wrapper czr-page">

    <?php $this->load->view('admin/pages/view/referral-head', [
        'czr_title' => $czr_title,
        'czr_crumb' => $czr_crumb,
        'czr_sub'   => $czr_sub,
    ]); ?>

    <section class="content">
        <div class="container-fluid">

            <?php $this->load->view('admin/pages/view/referral-stat-row'); ?>

            <div class="czr-card">
                <div class="czr-card__head">
                    <div>
                        <h2 class="czr-card__title">All referrals</h2>
                        <p class="czr-card__sub">
                            Rewards column shows what this referral has produced so far. A referral with no
                            rewards is normal - the programme may be off, or its milestone not reached yet.
                        </p>
                    </div>
                </div>
                <div class="czr-card__body czr-card__body--flush">
                    <div class="czr-tablewrap">
                        <table class="table"
                               data-toggle="table"
                               data-url="<?= base_url('admin/referral/ledger_list') ?>"
                               data-side-pagination="server"
                               data-pagination="true"
                               data-page-list="[10, 25, 50, 100]"
                               data-search="true"
                               data-show-columns="true"
                               data-show-refresh="true"
                               data-trim-on-search="false"
                               data-sort-name="id"
                               data-sort-order="desc"
                               data-mobile-responsive="true"
                               data-show-export="true"
                               data-export-types='["txt","excel"]'
                               data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th data-field="id" data-sortable="true">ID</th>
                                    <th data-field="referrer">Referrer</th>
                                    <th data-field="referee">Referred user</th>
                                    <th data-field="program">Program</th>
                                    <th data-field="code">Code used</th>
                                    <th data-field="source">Came via</th>
                                    <th data-field="status">Status</th>
                                    <th data-field="paid" data-sortable="false">Paid so far</th>
                                    <th data-field="rewards">Rewards</th>
                                    <th data-field="signup_ip" data-visible="false">Signup IP</th>
                                    <th data-field="created_at" data-sortable="true">Referred on</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
