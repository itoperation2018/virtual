<div class="modal fade" id="add_attend_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <span class="edit-title"><?php echo _l('invoice_item_edit_heading'); ?></span>
                    <span class="add-title"><?php echo _l('invoice_item_add_heading'); ?></span>
                </h4>
            </div>
            <?php echo form_open('admin/timesheet/manage',array('id'=>'add_attend')); ?>
            <?php echo form_hidden('itemid'); ?>
            <div class="modal-body">
              
                <div class="row">
                    <div class="col-md-12">
                       
                        <div class="col-md-6">
                            <?php echo render_date_input('date_in','Date In'); ?>
                        </div>
                        <div class="col-md-6">
                             <?php echo render_date_input('date_out','Date Out'); ?>
                        </div>
                     

                        <div class="col-md-6">
                              <?php echo render_datetime_input('time_in','Time In'); ?>
                        </div>

                        <div class="col-md-6">
                             <?php echo render_datetime_input('time_out','Time Out'); ?>
                        </div> 

                        <div class="col-md-6">
                         <?php echo render_select('staff_id',$items_groups,array('staffid','firstname'),'Staff'); ?>                   
                        </div>
                      </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
        <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
        <?php echo form_close(); ?>
    </div>
</div>
</div>
</div>
<script>
    // Maybe in modal? Eq convert to invoice or convert proposal to estimate/invoice
    if(typeof(jQuery) != 'undefined'){
        init_item_js();
    } else {
      window.addEventListener('load', function () {
        init_item_js();
     });
  }
// Items add/edit
function manage_attendance(form) {
    var data = $(form).serialize();
    var url = form.action;
    $.post(url, data).done(function (response) {
        response = JSON.parse(response);
        if (response.success == true) {
            var item_select = $('#item_select');
            if ($("body").find('.accounting-template').length > 0) {
                var group = item_select.find('[data-group-id="' + response.item.group_id + '"]');
                var _option = '<option data-subtext="' + response.item.long_description + '" value="' + response.item.itemid + '">(' + accounting.formatNumber(response.item.rate) + ') ' + response.item.description + '</option>';
                if (!item_select.hasClass('ajax-search')) {
                    if (group.length == 0) {
                        _option = '<optgroup label="' + (response.item.group_name == null ? '' : response.item.group_name) + '" data-group-id="' + response.item.group_id + '">' + _option + '</optgroup>';
                        if (item_select.find('[data-group-id="0"]').length == 0) {
                            item_select.find('option:first-child').after(_option);
                        } else {
                            item_select.find('[data-group-id="0"]').after(_option);
                        }
                    } else {
                        group.prepend(_option);
                    }
                }
                if (!item_select.hasClass('ajax-search')) {
                    item_select.selectpicker('refresh');
                } else {

                    item_select.contents().filter(function () {
                        return !$(this).is('.newitem') && $(this).is('.newitem-divider');
                    }).remove();

                    var clonedItemsAjaxSearchSelect = item_select.clone();
                    item_select.selectpicker('destroy').remove();
                    item_select = clonedItemsAjaxSearchSelect;
                    $("body").find('.items-wrapper').append(clonedItemsAjaxSearchSelect);
                    init_ajax_search('items', '#item_select.ajax-search', undefined, admin_url + 'items/search');
                }
                add_item_to_preview(response.item.itemid);
            } else {
                // Is general items view
                $('.table-invoice-items').DataTable().ajax.reload(null, false);
            }
            alert_float('success', response.message);
        }
        $('#add_attend_modal').modal('hide');
    }).fail(function (data) {
        alert_float('danger', data.responseText);
    });
    return false;
}
function init_item_js() {
     // Add item to preview from the dropdown for invoices estimates
    $("body").on('change', 'select[name="item_select"]', function () {
        var itemid = $(this).selectpicker('val');
        if (itemid != '' && itemid !== 'newitem') {
            add_item_to_preview(itemid);
        } else if (itemid == 'newitem') {
            // New item
            $('#add_attend_modal').modal('show');
        }
    });

    // Items modal show action
    $("body").on('show.bs.modal', '#add_attend_modal', function (event) {

        $('.affect-warning').addClass('hide');

        var $itemModal = $('#add_attend_modal');
        $('input[name="itemid"]').val('');
        $itemModal.find('input').not('input[type="hidden"]').val('');
        $itemModal.find('textarea').val('');
        $itemModal.find('select').selectpicker('val', '');
        $('select[name="tax2"]').selectpicker('val', '').change();
        $('select[name="tax"]').selectpicker('val', '').change();
        $itemModal.find('.add-title').removeClass('hide');
        $itemModal.find('.edit-title').addClass('hide');

        var id = $(event.relatedTarget).data('id');
        // If id found get the text from the datatable
        if (typeof (id) !== 'undefined') {

            $('.affect-warning').removeClass('hide');
            $('input[name="itemid"]').val(id);

            requestGetJSON('timesheet/get_item_by_id/' + id).done(function (response) {
               
                $itemModal.find('input[name="date_out"]').val(response.date_out);
                $itemModal.find('input[name="date_in"]').val(response.date_in);
                $itemModal.find('input[name="time_out"]').val(response.time_out);
                $itemModal.find('input[name="time_in"]').val(response.time_in);
               
               
                $itemModal.find('.add-title').addClass('hide');
                $itemModal.find('.edit-title').removeClass('hide');
            });

        }
    });

    $("body").on("hidden.bs.modal", '#add_attend_modal', function (event) {
        $('#item_select').selectpicker('val', '');
    });

    // Set validation for invoice item form
    _validate_form($('#add_attend'), {
        description: 'required',
        rate: {
            required: true,
        }
    }, manage_attendance);
}
</script>
