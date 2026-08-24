<div class="content-wrapper admin-tickets-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-life-ring mr-2 text-primary-theme"></i>Support Tickets</h4>
                    <p class="text-muted mb-0 small">Customer and seller support requests.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/tickets/ticket-types') ?>">Ticket Types</a></li>
                        <li class="breadcrumb-item active">Tickets</li>
                    </ol>
                </div>

            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <?php
                /*
                 * Ticket conversation modal.
                 *
                 * Rebuilt as a proper chat panel: header identity, a meta strip for the facts
                 * (type / raised / status / status changer) and a threaded body. Every id and
                 * class that assets/admin/custom/custom.js binds to is preserved - #user_name,
                 * #subject, #ticket_type, #status, #date_created, .change_ticket_status,
                 * #element, .ticket_msg, .scroll_div, #ticket_send_msg_form, #message_input,
                 * #submit_btn and the .uploadFile / .image-upload-section pair the media picker
                 * finds via closest('.form-group') - so the markup could change without the
                 * behaviour changing with it.
                 *
                 * The old markup also had an <h4> closed by </h2> and a stray </form> after
                 * .modal-body, both of which browsers were quietly repairing.
                 */
                ?>
                <div class="modal fade tkt-modal" tabindex="-1" role="dialog" aria-hidden="true" id="ticket_modal" data-backdrop="static" data-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered tkt-dialog" role="document">
                        <div class="modal-content tkt-content">

                            <div class="tkt-head">
                                <div class="tkt-avatar" id="tkt_avatar" aria-hidden="true"><i class="fas fa-user"></i></div>
                                <div class="tkt-head-text">
                                    <h5 class="tkt-person" id="user_name"></h5>
                                    <p class="tkt-subject" id="subject"></p>
                                </div>
                                <button type="button" class="tkt-close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="tkt-meta">
                                <span class="tkt-chip tkt-chip--type"><i class="fas fa-tag"></i><span id="ticket_type"></span></span>
                                <span class="tkt-chip"><i class="far fa-clock"></i><span id="date_created"></span></span>
                                <span class="tkt-status-wrap" id="status"><label class="badge badge-secondary"></label></span>
                                <select class="change_ticket_status tkt-select" aria-label="Change ticket status">
                                    <option value="">Change status&hellip;</option>
                                    <option value="<?= OPENED ?>">Open</option>
                                    <option value="<?= RESOLVED ?>">Resolve</option>
                                    <option value="<?= CLOSED ?>">Close</option>
                                    <option value="<?= REOPEN ?>">Reopen</option>
                                </select>
                            </div>

                            <?php
                            // The ticket's opening text. It lives on the ticket row rather than in
                            // ticket_messages, so it never appeared in this modal at all - the
                            // admin saw a subject line and an empty thread.
                            ?>
                            <div class="tkt-brief" id="tkt_brief">
                                <span class="tkt-brief-label"><i class="fas fa-quote-left"></i> Request</span>
                                <p id="ticket_description"></p>
                            </div>

                            <?php
                            // data-limit is what load_messages() pages by. It used to say 15 here
                            // while the modal-open handler overwrote it with 5, so a thread always
                            // opened showing five messages however tall the panel was.
                            $offset = 0;
                            $limit = 15;
                            ?>
                            <div class="tkt-thread" id="element">
                                <div class="ticket_msg" data-limit="<?= $limit ?>" data-offset="<?= $offset ?>" data-max-loaded="false"></div>
                                <div class="scroll_div"></div>
                            </div>

                            <div class="tkt-composer">
                                <form id="ticket_send_msg_form" action="<?= base_url('admin/tickets/send-message'); ?>" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="user_id" id="user_id">
                                    <input type="hidden" name="user_type" id="user_type">
                                    <input type="hidden" name="ticket_id" id="ticket_id">
                                    <?php // .form-group wraps BOTH the picker link and its target tray: the media
                                          // modal writes previews into $(link).closest('.form-group').find('.image-upload-section'). ?>
                                    <div class="form-group tkt-composer-group mb-0">
                                        <div class="container-fluid row image-upload-section tkt-tray"></div>
                                        <div class="tkt-composer-row">
                                            <a class="uploadFile img tkt-attach-btn" data-input='attachments[]' data-isremovable='1' data-is-multiple-uploads-allowed='1' data-toggle="modal" data-target="#media-upload-modal" title="Attach a file">
                                                <i class="fa fa-paperclip"></i>
                                            </a>
                                            <input type="text" name="message" id="message_input" placeholder="Write a reply&hellip;" class="tkt-input" autocomplete="off">
                                            <button type="submit" class="tkt-send" id="submit_btn"><i class="fas fa-paper-plane mr-1"></i>Send</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 main-content">
            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set mr-2"><i class="fas fa-life-ring"></i></span>
                    <h5 class="mb-0">Tickets</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3 ticket-filters">
                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="small text-muted mb-1" for="ticket_raised_by_filter">Raised By</label>
                            <select class="form-control form-control-sm" id="ticket_raised_by_filter">
                                <option value="">Customers &amp; Sellers</option>
                                <option value="customer">Customers only</option>
                                <option value="seller">Sellers only</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="small text-muted mb-1" for="ticket_status_filter">Status</label>
                            <select class="form-control form-control-sm" id="ticket_status_filter">
                                <option value="">All statuses</option>
                                <option value="<?= PENDING ?>">Pending</option>
                                <option value="<?= OPENED ?>">Opened</option>
                                <option value="<?= RESOLVED ?>">Resolved</option>
                                <option value="<?= CLOSED ?>">Closed</option>
                                <option value="<?= REOPEN ?>">Reopened</option>
                            </select>
                        </div>
                    </div>
                    <table class='table-striped' id="ticket_table" data-toggle="table" data-url="<?= base_url('admin/tickets/view_ticket_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="t.id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="ticket_queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-align='center'>ID</th>
                                <th data-field="ticket_type_id" data-sortable="false" data-visible="false" data-align='center'>Ticket Type Id</th>
                                <th data-field="ticket_type" data-sortable="false" data-align='center'>Ticket Type</th>
                                <th data-field="user_id" data-sortable="true" data-visible="false" data-align='center'>User Id</th>
                                <th data-field="username" data-sortable="true" data-align='center'>User Name</th>
                                <th data-field="raised_by" data-sortable="false" data-align='center'>Raised By</th>
                                <th data-field="subject" data-sortable="true" data-align='center'>Subject</th>
                                <th data-field="email" data-sortable="false" data-align='center'>Email</th>
                                <th data-field="description" data-sortable="false" data-align='center'>Description</th>
                                <th data-field="status" data-sortable="true" data-align='center'>Status</th>
                                <th data-field="last_updated" data-sortable="false" data-visible="false" data-align='center'>last_updated</th>
                                <th data-field="date_created" data-sortable="true" data-align='center'>Date Created</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
</div>
<!-- /.row -->
</div><!-- /.container-fluid -->
</section>

<style>
    .admin-tickets-page .text-primary-theme { color: var(--color-orange); }

    .admin-tickets-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-tickets-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 6px;
        border-radius: 10px 10px 0 0;
    }
    .admin-tickets-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-tickets-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-tickets-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-tickets-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-tickets-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-tickets-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-tickets-page table.table thead th {
        background: #fafafa;
        border-top: none;
        border-bottom: 2px solid rgba(0,0,0,0.06);
        color: var(--color-grey);
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
    }
    .admin-tickets-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-tickets-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-tickets-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-tickets-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }
    .admin-tickets-page .action-btn { display: inline-block; vertical-align: middle; }

    /* ============================ Ticket conversation modal ============================
       Scoped under #ticket_modal so nothing here can reach the rest of the admin panel. The
       AdminLTE .direct-chat component is no longer used at all - it fought the layout (a
       draggable card header inside a modal, bubbles with fixed tails) for no gain. */

    #ticket_modal .tkt-dialog { max-width: 760px; }
    #ticket_modal .tkt-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(0, 0, 0, .22);
    }

    /* ---- header ---- */
    #ticket_modal .tkt-head {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px 20px;
        background: linear-gradient(135deg, var(--color-orange) 0%, var(--color-orange-dark) 100%);
        color: #fff;
    }
    #ticket_modal .tkt-avatar {
        width: 46px; height: 46px; flex: none;
        border-radius: 50%;
        background: rgba(255, 255, 255, .22);
        border: 1px solid rgba(255, 255, 255, .35);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
    }
    #ticket_modal .tkt-head-text { min-width: 0; flex: 1; }
    #ticket_modal .tkt-person {
        margin: 0;
        font-size: 17px;
        font-weight: 600;
        line-height: 1.3;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    /* The CUSTOMER / SELLER badge the JS appends inside #user_name. */
    #ticket_modal .tkt-person .badge {
        background: rgba(255, 255, 255, .9);
        color: var(--color-orange-dark) !important;
        font-size: 10px;
        letter-spacing: .6px;
        vertical-align: middle;
    }
    #ticket_modal .tkt-subject {
        margin: 2px 0 0;
        font-size: 13.5px;
        opacity: .92;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    #ticket_modal .tkt-close {
        border: none;
        background: rgba(255, 255, 255, .18);
        color: #fff;
        width: 32px; height: 32px; flex: none;
        border-radius: 50%;
        font-size: 20px; line-height: 1;
        opacity: 1;
        transition: background .15s;
    }
    #ticket_modal .tkt-close:hover { background: rgba(255, 255, 255, .32); }

    /* ---- meta strip ---- */
    #ticket_modal .tkt-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        background: #fcfcfd;
        border-bottom: 1px solid rgba(0, 0, 0, .07);
    }
    #ticket_modal .tkt-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 11px;
        border-radius: 20px;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .1);
        font-size: 12.5px;
        color: var(--color-grey);
        white-space: nowrap;
    }
    #ticket_modal .tkt-chip i { font-size: 11px; opacity: .7; }
    #ticket_modal .tkt-chip--type { color: var(--color-orange-dark); border-color: rgba(255, 122, 0, .35); background: var(--color-orange-light); }
    #ticket_modal .tkt-status-wrap .badge { font-size: 11px; padding: 6px 11px; border-radius: 20px; letter-spacing: .4px; }
    #ticket_modal .tkt-select {
        margin-left: auto;
        max-width: 190px;
        padding: 6px 12px;
        border: 1px solid rgba(0, 0, 0, .12);
        border-radius: 20px;
        background: #fff;
        font-size: 12.5px;
        color: var(--color-grey);
        cursor: pointer;
    }
    #ticket_modal .tkt-select:focus {
        outline: none;
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }

    /* ---- opening request ---- */
    #ticket_modal .tkt-brief {
        padding: 12px 20px 14px;
        background: #fff;
        border-bottom: 1px solid rgba(0, 0, 0, .07);
    }
    #ticket_modal .tkt-brief-label {
        display: block;
        font-size: 10.5px;
        font-weight: 600;
        letter-spacing: .8px;
        text-transform: uppercase;
        color: var(--color-orange-dark);
        margin-bottom: 4px;
    }
    #ticket_modal .tkt-brief-label i { font-size: 9px; opacity: .6; }
    #ticket_modal .tkt-brief p {
        margin: 0;
        font-size: 13.5px;
        line-height: 1.5;
        color: #4a5057;
        max-height: 96px;
        overflow-y: auto;
        white-space: pre-line;
        word-wrap: break-word;
    }

    /* ---- thread ---- */
    #ticket_modal .tkt-thread {
        height: 46vh;
        min-height: 280px;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 18px 20px;
        background:
            radial-gradient(circle at 18% 12%, rgba(255, 122, 0, .05) 0, transparent 42%),
            radial-gradient(circle at 82% 88%, rgba(255, 122, 0, .05) 0, transparent 42%),
            #f6f7f9;
    }
    #ticket_modal .tkt-thread::-webkit-scrollbar { width: 8px; }
    #ticket_modal .tkt-thread::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, .16); border-radius: 8px; }
    #ticket_modal .tkt-thread::-webkit-scrollbar-thumb:hover { background: rgba(0, 0, 0, .28); }

    #ticket_modal .tkt-msg { display: flex; margin-bottom: 14px; }
    #ticket_modal .tkt-msg--in  { justify-content: flex-start; }
    #ticket_modal .tkt-msg--out { justify-content: flex-end; }

    #ticket_modal .tkt-bubble {
        max-width: 76%;
        padding: 10px 14px 11px;
        border-radius: 14px;
        font-size: 14px;
        line-height: 1.45;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .07);
        word-wrap: break-word;
        overflow-wrap: anywhere;
    }
    #ticket_modal .tkt-msg--in .tkt-bubble {
        background: #fff;
        color: #33383d;
        border-bottom-left-radius: 4px;
    }
    #ticket_modal .tkt-msg--out .tkt-bubble {
        background: linear-gradient(135deg, var(--color-orange) 0%, var(--color-orange-dark) 100%);
        color: #fff;
        border-bottom-right-radius: 4px;
    }
    #ticket_modal .tkt-bubble-head {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin-bottom: 3px;
        font-size: 11.5px;
    }
    #ticket_modal .tkt-msg-name { font-weight: 600; }
    #ticket_modal .tkt-msg-time { margin-left: auto; opacity: .65; white-space: nowrap; }
    #ticket_modal .tkt-msg--in .tkt-msg-name { color: var(--color-orange-dark); }
    #ticket_modal .tkt-msg-text:empty { display: none; }

    /* ---- attachments inside a bubble ---- */
    #ticket_modal .tkt-atch { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    #ticket_modal .tkt-atch-thumb {
        display: block;
        width: 92px; height: 92px;
        border-radius: 10px;
        overflow: hidden;
        background: rgba(0, 0, 0, .05);
        border: 1px solid rgba(0, 0, 0, .08);
    }
    #ticket_modal .tkt-msg--out .tkt-atch-thumb { border-color: rgba(255, 255, 255, .45); }
    #ticket_modal .tkt-atch-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .2s; }
    #ticket_modal .tkt-atch-thumb:hover img { transform: scale(1.06); }
    #ticket_modal .tkt-atch-file {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        max-width: 100%;
        padding: 7px 12px;
        border-radius: 20px;
        font-size: 12.5px;
        text-decoration: none;
        background: rgba(0, 0, 0, .05);
        color: #33383d;
        border: 1px solid rgba(0, 0, 0, .08);
    }
    #ticket_modal .tkt-atch-file:hover { background: rgba(0, 0, 0, .09); color: #000; }
    #ticket_modal .tkt-msg--out .tkt-atch-file {
        background: rgba(255, 255, 255, .2);
        border-color: rgba(255, 255, 255, .4);
        color: #fff;
    }
    #ticket_modal .tkt-msg--out .tkt-atch-file:hover { background: rgba(255, 255, 255, .32); color: #fff; }

    /* ---- thread notices (empty state / start of conversation / loader) ---- */
    #ticket_modal .tkt-notice {
        text-align: center;
        font-size: 12.5px;
        color: #8a9099;
        padding: 6px 0 14px;
    }
    #ticket_modal .tkt-empty { text-align: center; color: #8a9099; padding: 42px 20px; }
    #ticket_modal .tkt-empty i { font-size: 34px; opacity: .35; display: block; margin-bottom: 10px; }
    #ticket_modal .loader { text-align: center; padding-bottom: 10px; }
    #ticket_modal .loader img { height: 26px; }

    /* ---- composer ---- */
    #ticket_modal .tkt-composer { padding: 14px 20px 18px; background: #fff; border-top: 1px solid rgba(0, 0, 0, .07); }
    #ticket_modal .tkt-composer-row { display: flex; align-items: center; gap: 10px; }
    #ticket_modal .tkt-input {
        flex: 1;
        min-width: 0;
        padding: 11px 16px;
        border: 1px solid rgba(0, 0, 0, .13);
        border-radius: 24px;
        font-size: 14px;
        background: #fafafa;
    }
    #ticket_modal .tkt-input:focus {
        outline: none;
        background: #fff;
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }
    #ticket_modal .tkt-attach-btn {
        width: 42px; height: 42px; flex: none;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: #f1f2f4;
        color: var(--color-grey);
        cursor: pointer;
        transition: background .15s, color .15s;
    }
    #ticket_modal .tkt-attach-btn:hover { background: var(--color-orange-light); color: var(--color-orange-dark); }
    #ticket_modal .tkt-send {
        flex: none;
        border: none;
        border-radius: 24px;
        padding: 11px 22px;
        font-size: 14px;
        font-weight: 500;
        color: #fff;
        background: linear-gradient(135deg, var(--color-orange) 0%, var(--color-orange-dark) 100%);
        transition: filter .15s;
    }
    #ticket_modal .tkt-send:hover:not(:disabled) { filter: brightness(1.07); }
    #ticket_modal .tkt-send:disabled { opacity: .6; }

    /* Media-picker previews land in .image-upload-section; the picker's own markup is a set of
       bootstrap grid columns with heavy margins, which looked lost in a composer bar. */
    #ticket_modal .tkt-tray { padding: 0; margin: 0 0 4px; }
    #ticket_modal .tkt-tray:empty { display: none; }
    #ticket_modal .tkt-tray .image {
        margin: 0 8px 8px 0 !important;
        padding: 6px !important;
        max-width: 96px;
        flex: none;
        border-radius: 10px;
    }
    #ticket_modal .tkt-tray .image img { max-height: 66px; object-fit: cover; }

    @media (max-width: 575.98px) {
        #ticket_modal .tkt-bubble { max-width: 88%; }
        #ticket_modal .tkt-select { margin-left: 0; }
        #ticket_modal .tkt-send { padding: 11px 16px; }
    }
</style>
<!-- /.content -->
</div>