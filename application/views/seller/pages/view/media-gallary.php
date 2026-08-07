<div class="content-wrapper media-gallery-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-photo-video mr-2 text-primary-theme"></i>Media</h4>
                    <p class="text-muted mb-0 small">Images and files you've uploaded, ready to reuse across your products.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Media</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <div class="card attribute-card mb-4">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-cloud-upload-alt"></i></span>
                    <h5 class="mb-0">Upload New Media</h5>
                </div>
                <div class="card-body">
                    <div id="dropzone" class="dz-upload-zone"></div>
                    <div class="text-right mt-3">
                        <a href="javascript:void(0)" id="upload-files-btn" class="btn btn-primary-theme"><i class="fas fa-upload mr-1"></i>Upload</a>
                    </div>
                </div>
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-photo-video"></i></span>
                    <h5 class="mb-0">Media Library</h5>
                </div>
                <div class="card-body">
                    <div class="product-filters-bar row align-items-end">
                        <div class="col-md-4 mb-2">
                            <label class="filter-label"><i class="far fa-clock mr-1"></i>Date Range</label>
                            <input type="text" class="form-control" autocomplete="off" id="datepicker" placeholder="Select Date Range To Filter">
                            <input type="hidden" id="start_date">
                            <input type="hidden" id="end_date">
                            <input type="hidden" id="seller_id" value="<?= (int) $_SESSION['user_id']; ?>">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="filter-label"><i class="fas fa-file mr-1"></i>Media Type</label>
                            <select class="form-control" id="media-type">
                                <option value="">All Media Items</option>
                                <option value="image">Images</option>
                                <option value="audio">Audio</option>
                                <option value="video">Video</option>
                                <option value="archive">Archive</option>
                                <option value="spreadsheet">Spreadsheet</option>
                                <option value="documents">Documents</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2 d-flex">
                            <button type="button" class="btn btn-primary-theme mr-2" onclick="status_date_wise_search()">Filter</button>
                            <button type="button" class="btn btn-light border" onclick="resetfilters()">Reset</button>
                        </div>
                    </div>

                    <table class='table-striped' id='media-table' data-page-size="5" data-toggle="table" data-url="<?= base_url('seller/media/fetch') ?>" data-click-to-select="true" data-single-select='true' data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-query-params="mediaUploadParams">
                        <thead>
                            <tr>
                                <th data-field="state" data-checkbox="true"></th>
                                <th data-field="id" data-sortable="true" data-visible='false'>ID</th>
                                <th data-field="seller_id" data-sortable="true" data-visible='false'>Seller ID</th>
                                <th data-field="name" data-sortable="false">Name</th>
                                <th data-field="image" data-sortable="false">Image</th>
                                <th data-field="extension" data-sortable="false">Extension</th>
                                <th data-field="sub_directory" data-sortable="false">Sub directory</th>
                                <th data-field="size" data-sortable="false">Size</th>
                                <th data-field="operate" data-sortable="false">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .media-gallery-page .text-primary-theme { color: var(--color-orange); }

    .media-gallery-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .media-gallery-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .media-gallery-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
    }
    .media-gallery-page .header-icon.bg-set { background: var(--color-orange); }

    .media-gallery-page .dz-upload-zone {
        border: 2px dashed rgba(0,0,0,0.15);
        border-radius: 10px;
        background: #fafafa;
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .media-gallery-page .dz-upload-zone .dz-message {
        margin: 2.5em 0;
        text-align: center;
        width: 100%;
    }
    .media-gallery-page .dz-upload-zone:hover { border-color: var(--color-orange); }

    .media-gallery-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .media-gallery-page .btn-primary-theme:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }

    .media-gallery-page .product-filters-bar {
        background: #fafafa;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 1rem 1rem 0.25rem;
        margin: 0 0 1.25rem;
    }
    .media-gallery-page .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        margin-bottom: 6px;
    }
    .media-gallery-page .filter-label i { color: var(--color-orange); }
    .media-gallery-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }

    .media-gallery-page .fixed-table-toolbar { margin-bottom: 10px; }
    .media-gallery-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .media-gallery-page .fixed-table-toolbar .btn-group > .btn,
    .media-gallery-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .media-gallery-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .media-gallery-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .media-gallery-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .media-gallery-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .media-gallery-page table.table thead th {
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
    .media-gallery-page table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .media-gallery-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .media-gallery-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .media-gallery-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }
</style>

<script>
var myDropzone = null;

$(document).ready(function () {
    if (typeof Dropzone === 'undefined') {
        console.error('Dropzone library failed to load; media upload box cannot be initialized.');
        return;
    }
    Dropzone.autoDiscover = false;

    if (document.getElementById('dropzone')) {
        myDropzone = new Dropzone('#dropzone', {
            url: base_url + 'seller/media/upload',
            paramName: 'documents',
            acceptedFiles: '.jpg,.jpeg,.png,.gif,.webp,.bmp',
            clickable: true,
            autoProcessQueue: false,
            parallelUploads: 12,
            maxFiles: 12,
            autoDiscover: false,
            addRemoveLinks: true,
            timeout: 180000,
            dictRemoveFile: 'x',
            dictMaxFilesExceeded: 'Only 12 files can be uploaded at a time',
            dictResponseError: 'Error',
            uploadMultiple: true,
            dictDefaultMessage: '<p><button type="button" class="btn btn-primary-theme dz-browse">Select Files</button><br> or <br> Drag &amp; Drop Media Files Here</p>',
        });

        // Dropzone only auto-inserts the dictDefaultMessage markup when the target
        // element has the literal class "dropzone" - which we deliberately don't use
        // here (adding it makes this element a target of Dropzone's own autoDiscover
        // scan, which races ahead of this manual init and attaches a broken,
        // no-URL instance first, causing "Dropzone already attached" on this line).
        // So the message/button is built by hand instead.
        if (!myDropzone.element.querySelector('.dz-message')) {
            var dzMessage = document.createElement('div');
            dzMessage.className = 'dz-message';
            dzMessage.innerHTML = '<button type="button" class="btn btn-primary-theme dz-browse">Select Files</button><br> or <br> Drag &amp; Drop Media Files Here';
            myDropzone.element.appendChild(dzMessage);
        }

        myDropzone.on('sending', function (file, xhr, formData) {
            formData.append(csrfName, csrfHash);
            xhr.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    var response = JSON.parse(this.response);
                    csrfName = response.csrfName;
                    csrfHash = response.csrfHash;
                    if (response.error == false) {
                        Dropzone.forElement('#dropzone').removeAllFiles(true);
                        iziToast.success({ message: response.message });
                        $('#media-table').bootstrapTable('refresh');
                    } else {
                        iziToast.error({ title: 'Error', message: response.message });
                    }
                    $(file.previewElement).find('.dz-error-message').text(response.message);
                }
            };
        });

        $('#upload-files-btn').on('click', function (e) {
            e.preventDefault();
            myDropzone.processQueue();
        });
    }
});

$(document).ready(function () {
    $('#datepicker').daterangepicker({
        showDropdowns: true,
        alwaysShowCalendars: true,
        autoUpdateInput: false,
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });
});

$('#datepicker').on('apply.daterangepicker', function (ev, picker) {
        $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
        $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });
    $('#datepicker').on('cancel.daterangepicker', function () {
        $(this).val('');
        $('#start_date').val('');
        $('#end_date').val('');
    });

    function status_date_wise_search() {
        $('#media-table').bootstrapTable('refresh');
    }

    function resetfilters() {
        $('#datepicker').val('');
        $('#media-type').val('');
        $('#start_date').val('');
        $('#end_date').val('');
        $('#media-table').bootstrapTable('refresh');
    }

    function mediaUploadParams(p) {
        return {
            type: $('#media-type').val(),
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    function copyToClipboard(element) {
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val($(element).text()).select();
        document.execCommand('copy');
        $temp.remove();
    }

    $(document).on('click', '.delete-media', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Are You Sure!',
            text: "You won't be able to revert this!",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({
                type: 'GET',
                url: base_url + 'seller/media/delete/' + id,
                dataType: 'json'
            }).done(function (response) {
                if (response.csrfName && response.csrfHash) {
                    csrfName = response.csrfName;
                    csrfHash = response.csrfHash;
                }
                if (response.error === false) {
                    $('#media-table').bootstrapTable('refresh');
                    Swal.fire('Success', 'File Deleted!', 'success');
                } else {
                    Swal.fire('Oops...', response.message, 'error');
                }
            }).fail(function () {
                Swal.fire('Oops...', 'Something went wrong!', 'error');
            });
        });
    });

    $(document).on('click', '.copy-to-clipboard', function () {
        var $element = $(this).closest('tr').find('.path');
        copyToClipboard($element);
        iziToast.success({ message: 'Image path copied to clipboard' });
    });
    $(document).on('click', '.copy-relative-path', function () {
        var $element = $(this).closest('tr').find('.relative-path');
        copyToClipboard($element);
        iziToast.success({ message: 'Image path copied to clipboard' });
    });
</script>
