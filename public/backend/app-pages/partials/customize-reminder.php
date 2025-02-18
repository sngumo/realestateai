<?php
use Jenga\App\Request\Url;
?>
<script type="text/javascript">
    $(function(){
        
        $('#save-new-reminder').on('click', function(event){
            
            event.preventDefault();
            if($(this).hasClass('disabled')){
                return false;
            }
            
            $('header').show();
            $.bootstrapGrowl('Saving Custom Template', {
                type: "info",
                align: "center",
                offset: {from: 'top', amount: 50},
                delay: 15000,
                allow_dismiss: true
            });
            
            var form = $('#custom_msg_form');
            var data = form.serializeArray();
            var action = form.attr('action');
            var method = form.attr('method');
            
            $.ajax({
                url: action,
                method: method,
                cache: false,
                data: data
            }).done(function (feedback) {
                var response = JSON.parse(feedback);
                
                if(response.status === 1){

                    //activate refresh
                    activateRefresh();

                    //success swal
                    $('div.bootstrap-growl').remove();
                    $.bootstrapGrowl(response.title, {
                        type: "success",
                        align: "center",
                        offset: {from: 'top', amount: 50},
                        delay: 15000,
                        allow_dismiss: true
                    });

                    $('header').hide();
                    closeToolPanel();
                }
                else if(response.status === 0){

                    //error swal
                    swal({
                        title: response.title,
                        text: response.text,
                        type: "error",
                        width: "530px"
                    });
                }
            });
        });
        
        $('#regen-btn').on('click', function(e){
            
            e.preventDefault();
            
            //show preview
            $('#customize-reminder .section-body').hide();
            $('#customize-reminder .section-head').show();
            
            $('#preview-msg-holder div.panel-title').html('Generating Preview ...');
            
            <?php
            if($from_api == 'no'){
                echo 'var url = "'. Url::link('/business/reminder/parsecustom') .'";';
            }
            else{
                echo 'var url = "'. Url::link('/api/business/reminder/parsecustom') .'";';
            }
            ?>
                        
            var msg = $("#message").val();
            
            $.ajax({
                url: url,
                method: "POST",
                cache: false,
                data: {
                    message: msg,
                    alias: $('#message_templates_alias').val(),
                    invoice: $('#invoice_id').val()
                },
                error: function () {
                    $(document).trigger('connection:error');
                }
            }).done(function(response){
                
                $('#preview-msg-holder div.panel-title').html('Preview');
                $('#msg-preview').html(response);
                
                if($('#regen-section').is(":visible")){
                    $('#regen-section').hide();
                    
                    //show save
                    $('#save-container').show();
                    
                    //triger on load
                    $('#save-new-reminder').removeClass('disabled');
                }
                else{
                }
            });
        });
        
        $('#back-to-editor').on('click', function(e){
            e.preventDefault();
            
            $('#customize-reminder .section-body').show();
            $('#customize-reminder .section-head').hide();
            
            //return buttons
            $('#customize-reminder #save-container').hide();
            $('#customize-reminder #regen-section').show();
        });
    });
</script>
<div id="customize-reminder" style="padding-bottom: 10vh">
    <div class="section-head" style="display: none;">
        <div class="row">
            <div class="col">
                <div id="preview-msg-holder" class="panel panel-dark m-b-0">
                    <div class="panel-title">
                      Preview
                    </div>
                    <div id="msg-preview" class="panel-body">
                        <?= $sample ?>                  
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="section-body" style="">
        <div class="row m-b-10">
            <div class="col">
                <?= $label_message ?>
                <?= $message ?>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="notice">
                    <div class="alert-warning p-15" style="font-size: 14px;">
                    <?= $note_msg ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="regen-section" class="form-group bottom-btn-row">
        <div class="row">
            <div class="col">
                <a id="regen-btn" href="#" class="btn btn-block bottom-btn btn-light btn-rounded p-t-15" style="font-weight: bold">
                    <i class="fa fa-refresh"></i>
                    Preview Sample
                </a>
            </div>
        </div>
    </div>
    <div id="save-container" class="form-group bottom-btn-row" style="display: none;">
        <div class="row">
            <div class="col p-t-5 p-b-5">
                <a id="back-to-editor" href="#" class="btn btn-block btn-light btn-rounded">
                    <i class="fas fa-backward"></i>
                    <strong>BACK TO EDIT</strong>
                </a>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <button id="save-new-reminder" type="button" class="btn btn-success btn-block btn-rounded disabled">
                    <i class="fa fa-2x falist fa-text-width"></i>
                    Save Custom
                </button>
            </div>
        </div>
    </div>
</div>
