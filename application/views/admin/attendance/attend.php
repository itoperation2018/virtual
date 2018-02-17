    <div class="modal fade" id="ticket-service-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <?php echo form_open(admin_url('attendance/attendanceEntry'),array('id'=>'attendance-add-form')); ?>
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">
                        <span class="edit-title"><?php echo _l('ticket_service_edit'); ?></span>
                        <span class="add-title">Attendance<!-- <?php echo _l('new_service'); ?> --></span>
                    </h4>
                </div>
                <div class="modal-body">


    <div class="row">

                             <div class="col-md-6">
                                 <?php
                        echo render_select('currency',$currencies,array('id','name','symbol'),'proposal_currency',$selected,do_action('proposal_currency_disabled',$s_attrs));
                           ?>   
                     
                    </div>

                </div>
                    
                    <div class="row">    

                         <div class="col-md-6">
            <?php $value = (isset($contract) ? _d($contract->datestart) : _d(date('Y-m-d'))); ?>
            <?php echo render_date_input('datestart','contract_start_date',$value); ?>
          </div>
        <div class="col-md-6">
            <?php $value = (isset($contract) ? _d($contract->dateend) : ''); ?>
            <?php echo render_date_input('dateend','contract_end_date',$value); ?>
          </div> 
                         
                     <div class="col-md-6">
                                <div class="form-group">


                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="control-label" for="start_time">Start Time</label>
                                    </div>
                                    <div class="col-md-9">
                                        <?php echo render_datetime_input('start_time'); ?>
                                    </div>
                                </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="control-label" for="end_time">End Time</label>
                                    </div>
                                    <div class="col-md-9">
                                        <?php echo render_datetime_input('end_time'); ?>
                                    </div>
                                </div>
                                </div>
                            </div> 
                        
                           
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                    <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                </div>
            </div><!-- /.modal-content -->
            <?php echo form_close(); ?>
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <script>
        window.addEventListener('load',function(){
            _validate_form($('#attendance-add-form'),{datestart:'required'},manage_attendance_add);
            $('#ticket-service-modal').on('hidden.bs.modal', function(event) {
                $('#ticket-service-modal input[name="datestart"]').val('');
                $('.add-title').removeClass('hide');
                $('.edit-title').removeClass('hide');
            });
        });
        function manage_attendance_add(form) {
            var data = $('#datestart').val();
            var url = form.action;
           
            $.post(url, data).done(function(response) {
                if(ticketArea) {
                 response = JSON.parse(response);
                 if(response.success == true && typeof(response.id) != 'undefined'){
                    var group = $('select#service');
                    group.find('option:first').after('<option value="'+response.id+'">'+response.name+'</option>');
                    group.selectpicker('val',response.id);
                    group.selectpicker('refresh');
                }
                $('#ticket-service-modal').modal('hide');
            } else {
                window.location.reload();
            }
        });
            return false;
        }
        function new_service(){
            $('#ticket-service-modal').modal('show');
            $('.edit-title').addClass('hide');
        }
        function edit_service(invoker,id){
            var name = $(invoker).data('name');
            $('#additional').append(hidden_input('id',id));
            $('#ticket-service-modal input[name="name"]').val(name);
            $('#ticket-service-modal').modal('show');
            $('.add-title').addClass('hide');
        }
    </script>
