<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h4>Deliverable Locations</h4>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
            <li class="breadcrumb-item active">Deliverable Locations</li>
          </ol>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12 main-content">
          <div class="card content-area p-4">
            <div class="card-head">
              <h4 class="card-title">Product Deliverable Locations</h4>
              <p class="text-muted mb-0">Choose which zipcodes each of your products can be delivered to.</p>
              <?php if (!empty($gst_restriction['restricted'])) : ?>
                <div class="alert alert-warning mt-2 mb-0">
                  <i class="fas fa-exclamation-triangle mr-1"></i>
                  Your account is registered with a <strong>GST Enrollment Number</strong>, so you can only deliver within your own state<?= $gst_restriction['state'] !== '' ? ' (<strong>' . html_escape($gst_restriction['state']) . '</strong>)' : '' ?>.
                  <?php if (empty($gst_restriction['state_id'])) : ?>
                    Please update your state under <a href="<?= base_url('seller/profile') ?>">Profile</a> to a valid state before selecting zipcodes.
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="card-innr">
              <div class="gaps-1-5x"></div>
              <table id="deliverable-locations-table" class='table-striped' data-toggle="table" data-url="<?= base_url('seller/area/view_deliverable_products') ?>" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-query-params="queryParams">
                <thead>
                  <tr>
                    <th data-field="id" data-sortable="true">ID</th>
                    <th data-field="name" data-sortable="true" data-escape="false">Product</th>
                    <th data-field="deliverable_type" data-sortable="false" data-escape="false">Deliverable Type</th>
                    <th data-field="deliverable_zipcodes" data-sortable="false" data-escape="false">Zipcodes</th>
                    <th data-field="operate" data-sortable="false" data-escape="false">Actions</th>
                  </tr>
                </thead>
              </table>
            </div><!-- .card-innr -->
          </div><!-- .card -->
        </div>
      </div>
      <!-- /.row -->
    </div><!-- /.container-fluid -->
  </section>
  <!-- /.content -->
</div>

<!-- Modal to edit a product's deliverable location -->
<div class="modal fade" id="editDeliverableLocationModal" tabindex="-1" role="dialog" aria-labelledby="editDeliverableLocationLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="deliverable-location-form">
        <div class="modal-header">
          <h5 class="modal-title" id="editDeliverableLocationLabel">Deliverable Location &mdash; <span id="dl-product-name"></span></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="product_id" id="dl-product-id">
          <div class="form-group">
            <label>Deliverable Type</label>
            <select name="deliverable_type" id="dl-deliverable-type" class="form-control">
              <option value="<?= ALL ?>" <?= !empty($gst_restriction['restricted']) ? 'disabled' : '' ?>>All locations</option>
              <option value="<?= NONE ?>">Not deliverable anywhere</option>
              <option value="<?= INCLUDED ?>">Only selected zipcodes<?= !empty($gst_restriction['restricted']) ? ' (within your state only)' : '' ?></option>
              <option value="<?= EXCLUDED ?>" <?= !empty($gst_restriction['restricted']) ? 'disabled' : '' ?>>All except selected zipcodes</option>
            </select>
          </div>
          <div class="form-group" id="dl-zipcodes-wrapper" style="display:none;">
            <label>Zipcodes</label>
            <div class="mb-2">
              <button type="button" class="btn btn-outline-primary btn-sm" id="dl-select-state-zipcodes-btn">
                <i class="fas fa-map-marker-alt"></i> Select All Zipcodes in My State
              </button>
              <small class="text-muted d-block mt-1" id="dl-select-state-zipcodes-hint"></small>
            </div>
            <select name="deliverable_zipcodes[]" id="dl-zipcodes" class="form-control w-100" multiple></select>
          </div>
          <div id="dl-error-box" class="alert alert-danger d-none"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success" id="dl-save-btn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function queryParams(p) {
    return {
      limit: p.limit,
      sort: p.sort,
      order: p.order,
      offset: p.offset,
      search: p.search
    };
  }

  var sellerGstRestricted = <?= !empty($gst_restriction['restricted']) ? 'true' : 'false' ?>;
  var sellerState = <?= json_encode($gst_restriction['state'] ?? '') ?>;

  $(function () {
    var dlZipcodeSelect = $('#dl-zipcodes').select2({
      dropdownParent: $('#editDeliverableLocationModal'),
      theme: 'bootstrap4',
      placeholder: 'Search for zipcodes',
      minimumInputLength: 1,
      ajax: {
        url: base_url + 'seller/area/get_deliverable_zipcodes',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            search: params.term,
            page: params.page
          };
        },
        processResults: function (response, params) {
          params.page = params.page || 1;
          return {
            results: (response.data || []).map(function (row) {
              return { id: row.id, text: row.zipcode };
            }),
            pagination: { more: (params.page * 25) < response.total }
          };
        },
        cache: true
      }
    });

    function toggleZipcodesField() {
      var type = $('#dl-deliverable-type').val();
      if (type === '<?= INCLUDED ?>' || type === '<?= EXCLUDED ?>') {
        $('#dl-zipcodes-wrapper').show();
      } else {
        $('#dl-zipcodes-wrapper').hide();
      }
    }

    $('#dl-deliverable-type').on('change', toggleZipcodesField);

    // Bulk-select every zipcode in the seller's own profile state, so a state-restricted
    // (GST Enrollment Number) seller doesn't have to search and add zipcodes one at a time.
    $('#dl-select-state-zipcodes-btn').on('click', function () {
      var $btn = $(this).prop('disabled', true);
      var originalHtml = $btn.html();
      $btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...');

      $.ajax({
        url: base_url + 'seller/area/get_state_zipcodes',
        type: 'GET',
        dataType: 'json',
        success: function (response) {
          if (response.error) {
            $('#dl-error-box').removeClass('d-none').text(response.message);
            return;
          }
          dlZipcodeSelect.empty();
          (response.data || []).forEach(function (row) {
            dlZipcodeSelect.append(new Option(row.zipcode, row.id, true, true));
          });
          dlZipcodeSelect.trigger('change');
          $('#dl-error-box').addClass('d-none').text('');

          var count = (response.data || []).length;
          var note = 'Selected all ' + count + ' zipcode(s) in ' + (response.state || 'your state') + '.';
          if (response.total > count) {
            note += ' (showing first ' + count + ' of ' + response.total + ')';
          }
          $('#dl-select-state-zipcodes-hint').text(note);
        },
        error: function () {
          $('#dl-error-box').removeClass('d-none').text('Something went wrong fetching zipcodes for your state.');
        },
        complete: function () {
          $btn.prop('disabled', false).html(originalHtml);
        }
      });
    });

    $(document).on('click', '.edit-deliverable-location', function () {
      var id = $(this).data('id');
      var name = $(this).data('name');
      var type = String($(this).data('type'));
      var zipcodes = $(this).data('zipcodes') || [];

      $('#dl-product-id').val(id);
      $('#dl-product-name').text(name);
      $('#dl-error-box').addClass('d-none').text('');
      $('#dl-select-state-zipcodes-hint').text('');

      var restrictedTypeBlocked = sellerGstRestricted && (type === '<?= ALL ?>' || type === '<?= EXCLUDED ?>');
      $('#dl-deliverable-type').val(restrictedTypeBlocked ? '<?= NONE ?>' : type);
      if (restrictedTypeBlocked) {
        $('#dl-error-box').removeClass('d-none').text('This product was previously set to deliver everywhere, but your account is now restricted to ' + (sellerState || 'your own state') + '. Please choose "Only selected zipcodes" and pick zipcodes within your state, or leave it as "Not deliverable anywhere".');
      }

      dlZipcodeSelect.empty();
      zipcodes.forEach(function (zipcode) {
        var option = new Option(zipcode.text, zipcode.id, true, true);
        dlZipcodeSelect.append(option);
      });
      dlZipcodeSelect.trigger('change');

      toggleZipcodesField();
      $('#editDeliverableLocationModal').modal('show');
    });

    $('#deliverable-location-form').on('submit', function (e) {
      e.preventDefault();
      $('#dl-save-btn').prop('disabled', true);

      $.ajax({
        url: base_url + 'seller/area/update_deliverable_location',
        type: 'POST',
        dataType: 'json',
        data: $(this).serialize() + '&' + csrfName + '=' + csrfHash,
        success: function (response) {
          csrfHash = response.csrfHash;
          if (response.error) {
            $('#dl-error-box').removeClass('d-none').text(response.message);
          } else {
            $('#editDeliverableLocationModal').modal('hide');
            $('#deliverable-locations-table').bootstrapTable('refresh');
            iziToast.success({ title: 'Success', message: response.message, position: 'topRight' });
          }
        },
        error: function () {
          $('#dl-error-box').removeClass('d-none').text('Something went wrong. Please try again.');
        },
        complete: function () {
          $('#dl-save-btn').prop('disabled', false);
        }
      });
    });
  });
</script>
