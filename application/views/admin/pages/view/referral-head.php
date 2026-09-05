<?php
/**
 * Shared chrome for every Refer & Earn screen: the stylesheet link, the
 * breadcrumb and the page title.
 *
 * The five screens each used to build their own header out of AdminLTE's
 * content-header / float-sm-right breadcrumb, which is why they drifted - the
 * titles sat at different sizes and none of them explained what the screen was
 * for. One partial keeps them identical.
 *
 * Callers set $czr_title, $czr_sub (optional one-line explanation) and
 * $czr_crumb (the trailing breadcrumb label).
 *
 * ESCAPING CONTRACT. $czr_title and $czr_crumb are PLAIN TEXT - pass "Refer &
 * Earn", not "Refer &amp; Earn", because they are escaped here. A pre-escaped
 * title was escaped a second time and the page rendered the entity itself.
 *
 * $czr_sub is the exception: it is printed as markup so a sub-line can carry an
 * em dash or a link. That makes it developer-authored copy only - never put a
 * shop name, a user name or anything else from the database into it.
 */
$czr_title = isset($czr_title) ? $czr_title : 'Refer & Earn';
$czr_sub   = isset($czr_sub) ? $czr_sub : '';
$czr_crumb = isset($czr_crumb) ? $czr_crumb : '';
?>
<link rel="stylesheet"
      href="<?= base_url('assets/admin/css/cretzo/admin-referral.css') ?>?v=<?= @filemtime(FCPATH . 'assets/admin/css/cretzo/admin-referral.css') ?: time() ?>">

<section class="content-header czr-header">
    <div class="container-fluid">

        <div class="czr-banner">
            <?php /* The tabs live in the banner rather than under it: these five
                     screens are one tool, and an admin moving between them should
                     not have to go back to the sidebar to do it. */ ?>
            <div class="czr-banner__top">
                <ol class="czr-crumbs">
                    <li><a href="<?= base_url('admin/home') ?>">Home</a></li>
                    <li><a href="<?= base_url('admin/referral/programs') ?>">Refer &amp; Earn</a></li>
                    <?php if ($czr_crumb !== '') { ?>
                        <li aria-current="page"><?= html_escape($czr_crumb) ?></li>
                    <?php } ?>
                </ol>
                <div class="czr-head__actions">
                    <?= isset($czr_actions) ? $czr_actions : '' ?>
                </div>
            </div>

            <div class="czr-banner__main">
                <span class="czr-banner__mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 12v8H4v-8"></path>
                        <path d="M2 8h20v4H2z"></path>
                        <path d="M12 22V8"></path>
                        <path d="M12 8H7.5a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8z"></path>
                        <path d="M12 8h4.5a2.5 2.5 0 0 0 0-5C13 3 12 8 12 8z"></path>
                    </svg>
                </span>
                <div class="czr-banner__copy">
                    <h1 class="czr-head__title"><?= html_escape($czr_title) ?></h1>
                    <?php if ($czr_sub !== '') { ?>
                        <p class="czr-head__sub"><?= $czr_sub ?></p>
                    <?php } ?>
                </div>
            </div>

            <?php
            /* Which screen is showing, matched on the controller method so a query
               string (?status=queue) does not knock the tab off. */
            $czr_here = $this->uri->segment(3, 'programs');
            $czr_tabs = [
                'programs'    => ['Programs', 'admin/referral/programs'],
                'queue'       => ['Rewards', 'admin/referral/queue?status=queue'],
                'ledger'      => ['Ledger', 'admin/referral/ledger'],
                'ambassadors' => ['Ambassadors', 'admin/referral/ambassadors'],
                'report'      => ['Cost report', 'admin/referral/report'],
            ];
            ?>
            <nav class="czr-nav" aria-label="Refer & Earn sections">
                <?php foreach ($czr_tabs as $key => $tab) { ?>
                    <a class="czr-nav__item <?= $czr_here === $key ? 'is-active' : '' ?>"
                       href="<?= base_url($tab[1]) ?>"><?= $tab[0] ?></a>
                <?php } ?>
            </nav>
        </div>
    </div>
</section>
