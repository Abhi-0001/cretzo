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
                <div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" id="ticket_modal" data-backdrop="static" data-keyboard="false">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="user_name"></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <?php

                            ?>
                            <div class="modal-body">
                                <div class="card direct-chat direct-chat-primary">
                                    <div class="card-header ui-sortable-handle" style="cursor: move;">
                                        <h4 class="" id="ticket_type">
                                            </h2>
                                            <h3 class="card-title" id="subject"></h3>
                                            <span id="status"><label class="badge badge-secondary ml-2"></label></span><br>
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <p id="date_created"></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <select class="form-control-sm w-100 change_ticket_status">
                                                        <option value="">Change Ticket Status</option>
                                                        <option value=<?= OPENED ?>>OPEN</option>
                                                        <option value=<?= RESOLVED ?>>RESOLVE</option>
                                                        <option value=<?= CLOSED ?>>CLOSE</option>
                                                        <option value=<?= REOPEN ?>>REOPEN</option>
                                                    </select>
                                                </div>
                                            </div>
                                    </div>
                                    <?php
                                    $offset = 0;
                                    $limit = 15;
                                    ?>
                                    <div class="card-body">
                                        <div class="direct-chat-messages" id="element">
                                            <div class="ticket_msg" data-limit="<?= $limit ?>" data-offset="<?= $offset ?>" data-max-loaded="false">
                                            </div>
                                            <div class="scroll_div"></div>
                                        </div>
                                    </div>
                                    <!-- /.card-body -->
                                    <div class="card-footer">
                                        <form class="form-horizontal " id="ticket_send_msg_form" action="<?= base_url('admin/tickets/send-message'); ?>" method="POST" enctype="multipart/form-data">
                                            <div class="row">
                                                <div class="input-group">
                                                    <input type="hidden" name="user_id" id="user_id">
                                                    <input type="hidden" name="user_type" id="user_type">
                                                    <input type="hidden" name="ticket_id" id="ticket_id">
                                                    <input type="text" name="message" id="message_input" placeholder="Type Message ..." class="form-control">
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <span class="input-group-append">
                                                    <div class="form-group">
                                                        <?php

                                                        if (file_exists(FCPATH  . @$fetched_data[0]['attachments']) && !empty(@$fetched_data[0]['attachments'])) {
                                                            $fetched_data[0]['attachments'] = get_image_url($fetched_data[0]['attachments']);
                                                        ?>
                                                            <div class="container-fluid row image-upload-section">
                                                                <div class="col-md-3 col-sm-12 shadow bg-white rounded m-3 p-3 text-center grow">
                                                                    <div class='image-upload-div'><img class="img-fluid mb-2" src="<?= $fetched_data[0]['attachments'] ?>" alt="Image Not Found"></div>
                                                                    <input type="hidden" name="attachments[]" value='<?= $fetched_data[0]['attachments'] ?>'>
                                                                </div>
                                                            </div>
                                                        <?php
                                                        } else { ?>
                                                            <div class="container-fluid row image-upload-section">
                                                            </div>
                                                        <?php } ?>
                                                        <a class="uploadFile img btn btn-primary text-white " data-input='attachments[]' data-isremovable='1' data-is-multiple-uploads-allowed='1' data-toggle="modal" data-target="#media-upload-modal" value="Upload Photo"> <i class="fa fa-paperclip"></i></a>
                                                        <button type="submit" class="btn btn-primary" id="submit_btn">Send</button>
                                                    </div>
                                                </span>
                                            </div>

                                        </form>
                                    </div>
                                    <!-- /.card-footer-->
                                </div>
                            </div>
                            </form>
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
                    <table class='table-striped' id="ticket_table" data-toggle="table" data-url="<?= base_url('admin/tickets/view_ticket_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="t.id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="ticket_queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-align='center'>ID</th>
                                <th data-field="ticket_type_id" data-sortable="false" data-visible="false" data-align='center'>Ticket Type Id</th>
                                <th data-field="ticket_type" data-sortable="false" data-align='center'>Ticket Type</th>
                                <th data-field="user_id" data-sortable="true" data-visible="false" data-align='center'>User Id</th>
                                <th data-field="username" data-sortable="true" data-align='center'>User Name</th>
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

    /* Ticket conversation modal - re-themed to orange, no class/id renamed (JS in
       assets/admin/custom/custom.js binds to these directly). */
    .admin-tickets-page ~ .modal#ticket_modal .card-header,
    #ticket_modal .direct-chat .card-header {
        background: var(--color-orange) !important;
        color: #fff;
    }
    #ticket_modal .change_ticket_status:focus { border-color: var(--color-orange); box-shadow: 0 0 0 .15rem var(--color-orange-light); }
    #ticket_modal #submit_btn { background: var(--color-orange); border-color: var(--color-orange); }
    #ticket_modal #submit_btn:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); }
</style>
<!-- /.content -->
</div>