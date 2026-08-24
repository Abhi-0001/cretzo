<?php
/*
| Shared look for the order-detail screen in BOTH panels.
|
| These rules were written inline in admin/pages/forms/edit-orders.php and scoped to
| .admin-view-order-page. The seller panel shows the same information on the same kind of
| screen but had none of it, so its copy still looked like stock AdminLTE. Rather than
| duplicating ~140 lines of CSS, the block moved here and is scoped to .view-order-page,
| which both wrappers now carry. Both panels already load assets/admin/css/cretzo/cretzo.css,
| so the --color-* variables these rules use resolve in either one.
*/
?>
<style>
    .view-order-page .text-primary-theme { color: var(--color-orange); }

    .view-order-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .view-order-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .view-order-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .view-order-page .header-icon.bg-set { background: var(--color-orange); }

    /* The order summary is a label/value table, not a header+rows list table, so it gets its
       own treatment rather than the uppercase-header style used on this page's list tables. */
    .view-order-page .order-detail-table th {
        border-top: none;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        color: var(--color-grey);
        font-weight: 600;
        width: 220px;
        white-space: nowrap;
        vertical-align: top;
        padding-top: 14px;
    }
    .view-order-page .order-detail-table td {
        border-top: none;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        vertical-align: top;
        padding-top: 14px;
    }
    .view-order-page .order-detail-table tr:last-child th,
    .view-order-page .order-detail-table tr:last-child td { border-bottom: none; }

    /* Per-seller and per-item cards used the default AdminLTE "info" skin (a blue accent bar) -
       restyled to the same soft shadow/rounded look used everywhere else in the redesigned panel. */
    .view-order-page .card-info {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .view-order-page .card-info.card-outline { border-top: 3px solid var(--color-orange); }

    .view-order-page .form-control:focus { border-color: var(--color-orange); box-shadow: 0 0 0 .15rem var(--color-orange-light); }

    /* Same action-button spacing fix applied on every other page this engagement - these rows
       carry several icons (Order Tracking / View Product / Refund / Send Mail) that would
       otherwise crowd or wrap unpredictably. */
    .view-order-page .action-btn { display: inline-block; vertical-align: middle; }
    .view-order-page .grow { transition: box-shadow .15s ease; }
    .view-order-page .grow:hover { box-shadow: 0 2px 10px rgba(0,0,0,0.08); }

    /* Per-item cards. Deliberately NOT using Bootstrap's .card class here - .card is a flex
       container by default, so a stray direct-child link (the old Refund button) stretched to
       fill the full card width instead of sizing to its own content. */
    .view-order-page .order-item-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin: 0;
    }
    .view-order-page .order-item-card {
        display: flex;
        flex-direction: column;
        width: 260px;
        background: #fff;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        padding: 14px;
        transition: box-shadow .15s ease;
    }
    .view-order-page .order-item-card:hover { box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
    .view-order-page .order-item-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    .view-order-page .order-item-select input { width: 16px; height: 16px; margin: 0; cursor: pointer; }
    .view-order-page .order-item-media {
        text-align: center;
        background: #fafafa;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 10px;
    }
    .view-order-page .order-item-media img { max-height: 110px; max-width: 100%; object-fit: contain; }
    .view-order-page .order-item-name {
        font-weight: 600;
        font-size: 14px;
        color: #2b2f33;
        margin-bottom: 8px;
        line-height: 1.3;
    }
    .view-order-page .order-item-meta { margin-bottom: 10px; }
    .view-order-page .oi-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        padding: 4px 0;
        border-bottom: 1px solid rgba(0,0,0,0.04);
        font-size: 13px;
    }
    .view-order-page .oi-row:last-child { border-bottom: none; }
    .view-order-page .oi-label { color: var(--color-grey); }
    .view-order-page .oi-value { color: #2b2f33; font-weight: 500; text-align: right; }
    .view-order-page .order-item-mail-status {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 10px;
    }
    .view-order-page .order-item-mail-status select { flex: 1; }
    .view-order-page .order-item-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: auto;
        padding-top: 10px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .view-order-page .order-item-actions .btn { white-space: nowrap; }
</style>
