<div class="modal fade" id="salary_template_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <span class="edit-title"><?php echo _l('invoice_item_edit_heading'); ?></span>
                    <span class="add-title"><?php echo _l('invoice_item_add_heading'); ?></span>
                </h4>
            </div>
            <?php echo form_open('admin/salary_template/manage',array('id'=>'salary_tpl')); ?>
            <?php echo form_hidden('itemid'); ?>
            <div class="modal-body">
              
                <div class="row">
                    <div class="col-md-12">

            <div class="col-md-4"><?php echo render_input('salary_grade','Salary Grade'); ?></div>
            <div class="col-md-4"><?php echo render_input('basic_salary','Basic Salary'); ?></div>
            <div class="col-md-4"><?php echo render_input('overtime_rate','Overtime Rate');?></div>
           
            <div class="col-md-4"><?php echo render_input('house_rent_allowance','House Rent Allowance'); ?></div>
            <div class="col-md-4"><?php echo render_input('medicle_allowance','Medicle Allowance'); ?></div>
 
            <div class="col-md-4"><?php echo render_input('provident_fund','Provident Fund '); ?></div>
            <div class="col-md-4"><?php echo render_input('tax_deduction','Tax Deduction'); ?></div>

            <div class="col-md-4"><?php echo render_input('gross_salary','Gross Salary : '); ?></div>
            <div class="col-md-4"><?php echo render_input('total_deduction','Total Deduction : : '); ?></div>
            <div class="col-md-4"><?php echo render_input('net_salary','Net Salary : '); ?></div>
            

                        
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
function manage_salary_tempalte(form) {
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
        $('#salary_template_modal').modal('hide');
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
            $('#salary_template_modal').modal('show');
        }
    });

    // Items modal show action
    $("body").on('show.bs.modal', '#salary_template_modal', function (event) {

        $('.affect-warning').addClass('hide');

        var $itemModal = $('#salary_template_modal');
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

    $("body").on("hidden.bs.modal", '#salary_template_modal', function (event) {
        $('#item_select').selectpicker('val', '');
    });

    // Set validation for invoice item form
    _validate_form($('#salary_tpl'), {
        description: 'required',
        rate: {
            required: true,
        }
    }, manage_salary_tempalte);
}
</script>
