<div class="modal fade " id='media-upload-modal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaUploadModalTitle">Media</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="col-md-12 main-content">
                    <div class="content-area p-4">
                        <div class="card-innr">
                            <div class="gaps-1-5x"></div>
                            <input type='hidden' name='media_type' id='media_type' value='image'>
                            <input type='hidden' name='current_input'>
                            <input type='hidden' name='seller_id' value="<?= $_SESSION['user_id'] ?>">
                            <input type='hidden' name='remove_state'>
                            <input type='hidden' name='multiple_images_allowed_state'>
                            <div class="col-md-12 mt-3 mb-3 mb-5">
                                <!-- Change /upload-target to your upload address -->
                                <div id="media-modal-dropzone" class="dropzone"></div>
                                <br>
                                <a href="" id="media-modal-upload-files-btn" class="btn btn-success float-right">Upload</a>
                            </div>
                            <div class="alert alert-warning">Select media and click choose media</div>
                            <div id="toolbar">
                               
                                <button id='upload-media' class="btn btn-danger">
                                    <i class="fa fa-plus"></i> Choose Media
                                </button>
                            </div>
                            <table class='table-striped' data-toolbar="#toolbar" id='media-upload-table' data-page-size="5" data-toggle="table" data-url="<?= base_url('seller/media/fetch') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-query-params="mediaParams">
                                <thead>
                                    <tr>
                                        <th data-field="state" data-checkbox="true"></th>
                                        <th data-field="id" data-sortable="true" data-visible='false'>ID</th>
                                        <th data-field="image" data-sortable="false">Image</th>
                                        <th data-field="name" data-sortable="false">Name</th>
                                        <th data-field="size" data-sortable="false">Size</th>
                                        <th data-field="extension" data-sortable="false" data-visible='false'>Extension</th>
                                        <th data-field="sub_directory" data-sortable="false" data-visible='false'>Sub directory</th>
                                    </tr>
                                </thead>
                            </table>
                        </div><!-- .card-innr -->
                    </div><!-- .card -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Dropzone.js auto-discovers any element with class="dropzone" (the div below) at
    // DOMContentLoaded and auto-inits it with no upload URL, throwing "Error: No URL
    // provided" — on every seller page, since this modal is in the shared footer. jQuery's
    // ready handler is always registered before dropzone.js's own internal one (jQuery
    // loads in <head>, dropzone.js loads near the end of the body), so setting the flag
    // here reliably wins the race and disables auto-discovery before it can run.
    $(document).ready(function () {
        if (typeof Dropzone !== 'undefined') {
            Dropzone.autoDiscover = false;
        }
    });

    var mediaModalDropzone = null;
    var mediaModalCurrentTrigger = null;

    function mediaParams(p) {
        return {
            type: $('#media-upload-modal').find('#media_type').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    function getMediaModalAcceptedFiles(mediaType) {
        switch (mediaType) {
            case 'archive,document':
                return '.zip,.rar,.7z,.pdf,.doc,.docx,.xls,.xlsx,.txt';
            case 'video':
                return '.mp4,.mov,.avi,.mkv,.webm';
            case 'image':
            default:
                return '.jpg,.jpeg,.png,.gif,.webp,.bmp';
        }
    }

    // Only instantiated the first time the modal is actually shown (not at page load) so
    // pages that never open this modal never touch Dropzone at all.
    function initMediaModalDropzone(mediaType) {
        var acceptedFiles = getMediaModalAcceptedFiles(mediaType);

        if (mediaModalDropzone) {
            mediaModalDropzone.options.acceptedFiles = acceptedFiles;
            if (mediaModalDropzone.hiddenFileInput) {
                mediaModalDropzone.hiddenFileInput.setAttribute('accept', acceptedFiles);
            }
            return;
        }

        var dropzoneEl = document.querySelector('#media-upload-modal #media-modal-dropzone');
        if (!dropzoneEl) {
            return;
        }

        Dropzone.autoDiscover = false;
        mediaModalDropzone = new Dropzone(dropzoneEl, {
            url: base_url + 'seller/media/upload',
            paramName: 'documents',
            acceptedFiles: acceptedFiles,
            clickable: true,
            autoProcessQueue: false,
            parallelUploads: 12,
            maxFiles: 12,
            addRemoveLinks: true,
            timeout: 180000,
            dictRemoveFile: 'x',
            dictMaxFilesExceeded: 'Only 12 files can be uploaded at a time',
            dictResponseError: 'Error',
            uploadMultiple: true,
            dictDefaultMessage: '<p><button type="button" class="btn btn-success dz-browse">Select Files</button><br> or <br> Drag &amp; Drop Media Files Here</p>',
        });

        $(dropzoneEl).on('click', '.dz-browse', function (e) {
            e.preventDefault();
            if (mediaModalDropzone.hiddenFileInput) {
                mediaModalDropzone.hiddenFileInput.click();
            }
        });

        mediaModalDropzone.on('sending', function (file, xhr, formData) {
            formData.append(csrfName, csrfHash);
            formData.append('media_type', $('#media-upload-modal').find('#media_type').val());
            xhr.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    var response = JSON.parse(this.response);
                    csrfName = response.csrfName;
                    csrfHash = response.csrfHash;
                    if (response.error == false) {
                        Dropzone.forElement(dropzoneEl).removeAllFiles(true);
                        $('#media-upload-table').bootstrapTable('refresh');
                        if (typeof iziToast !== 'undefined') {
                            iziToast.success({ message: response.message });
                        }
                    } else if (typeof iziToast !== 'undefined') {
                        iziToast.error({ title: 'Error', message: response.message });
                    }
                    $(file.previewElement).find('.dz-error-message').text(response.message);
                }
            };
        });
    }

    $(document).on('click', '#media-upload-modal #media-modal-upload-files-btn', function (e) {
        e.preventDefault();
        if (mediaModalDropzone) {
            mediaModalDropzone.processQueue();
        }
    });

    // Captures which .uploadFile trigger opened the modal (data-input/data-media_type/etc.)
    // so #upload-media below knows where to put the chosen file.
    $(document).on('show.bs.modal', '#media-upload-modal', function (event) {
        var $trigger = $(event.relatedTarget);
        mediaModalCurrentTrigger = $trigger.length ? $trigger : null;

        var mediaType = ($trigger.length && $trigger.is('[data-media_type]')) ? $trigger.data('media_type') : 'image';
        var isRemovable = $trigger.length ? $trigger.data('isremovable') : 0;
        var isMultipleAllowed = $trigger.length ? $trigger.data('is-multiple-uploads-allowed') : 0;
        var input = $trigger.length ? $trigger.data('input') : '';

        $(this).find('#media_type').val(mediaType);
        $(this).find('input[name="current_input"]').val(input);
        $(this).find('input[name="remove_state"]').val(isRemovable);
        $(this).find('input[name="multiple_images_allowed_state"]').val(isMultipleAllowed);

        initMediaModalDropzone(mediaType);

        $('#media-upload-table').bootstrapTable('refreshOptions', {
            singleSelect: (isMultipleAllowed == 1) ? false : true,
        });
        $('#media-upload-table').bootstrapTable('refresh');
    });

    $(document).on('click', '#media-upload-modal #upload-media', function () {
        var $result = $('#media-upload-table').bootstrapTable('getSelections');
        if (!$result.length || !mediaModalCurrentTrigger) {
            return;
        }

        var mediaType = $('#media-upload-modal').find('#media_type').val();
        var input = $('#media-upload-modal').find('input[name="current_input"]').val();
        var isRemovable = $('#media-upload-modal').find('input[name="remove_state"]').val();
        var isMultipleAllowed = $('#media-upload-modal').find('input[name="multiple_images_allowed_state"]').val();
        var removableBtn = (isRemovable == '1') ? '<button type="button" class="remove-image btn btn-danger btn-xs mt-3">Remove</button>' : '';

        // The trigger isn't always wrapped in .form-group (e.g. the digital-product
        // Upload button just sits next to .image-upload-section inside an #id'd
        // container), so fall back to the nearest ancestor that has an id at all.
        var $scope = mediaModalCurrentTrigger.closest('.form-group');
        if (!$scope.length) {
            $scope = mediaModalCurrentTrigger.closest('[id]');
        }
        var $imageSection = $scope.find('.image-upload-section').first();
        if (!$imageSection.length) {
            return;
        }
        $imageSection.find('.image').removeClass('d-none');

        if (isMultipleAllowed == '1') {
            for (var i = 0; i < $result.length; i++) {
                var itemPath = base_url + $result[i].sub_directory + $result[i].name;
                $imageSection.append('<div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image"><div class="image-upload-div"><img class="img-fluid" alt="' + $result[i].name + '" title="' + $result[i].name + '" src="' + itemPath + '"><input type="hidden" name="' + input + '" value="' + $result[i].sub_directory + $result[i].name + '"></div>' + removableBtn + '</div>');
            }
        } else {
            var path = base_url + $result[0].sub_directory + $result[0].name;
            var subDirectory = $result[0].sub_directory + $result[0].name;
            var previewIconType = mediaType.indexOf(',') > -1 ? 'other' : mediaType;
            var previewSrc = (mediaType != 'image') ? base_url + 'assets/admin/images/' + previewIconType + '-file.png' : path;
            $imageSection.html('<div class="col-md-6 col-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image"><div class="image-upload-div"><img class="img-fluid" alt="' + $result[0].name + '" title="' + $result[0].name + '" src="' + previewSrc + '"><input type="hidden" name="' + input + '" value="' + subDirectory + '"></div>' + removableBtn + '</div>');
        }

        $('#media-upload-modal').modal('hide');
    });

    $(document).on('click', '.remove-image', function (e) {
        e.preventDefault();
        $(this).closest('.image').remove();
    });
</script>
