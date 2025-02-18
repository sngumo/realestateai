<?php
use Jenga\App\Request\Url;
?>
<script type="text/javascript">
$(function(){
    //send receipt
    $('#receipt-container #send-receipt').attr('disabled', '');
    
    //load receipt message
    $('#receipt-container #mediumselect').on('change', function(){
        
        var url = "<?= Url::link('/api/business/payments/loadreceiptmsg') ?>";
        var id = $('#receipt-container #payment').val();
        
        if(id !== ''){
            
            $('#receiptmsg').focus().val('loading receipt ...');
            
            $.ajax({
                url: url +'/'+ id
            }).done(function(response){
                
                $('#receiptmsg').val(response);
                $('#send-receipt').removeAttr('disabled');
            });
        }
        else{
            $('#receipt-container #payment').css('border', '1px solid red');
        }
    });
    
    //send receipt
    $('#receipt-container #send-receipt').on('click', function(event){
            
        event.preventDefault();
        
        $('header').show();
        $.bootstrapGrowl('Sending Receipt', {
            type: "info",
            align: "center",
            offset: {from: 'top', amount: 50},
            delay: 15000,
            allow_dismiss: true
        });

        var form = $('#receiptform');
        var action = form.attr('action');
        var method = form.attr('method');
        
        $.ajax({
            url: action,
            method: method,
            cache: false,
            data: {
                reminder: $('#reminder').val(),
                message: $('#receiptmsg').val(),
                medium: $('#mediumselect').val(),
                payment: $('#payment').val()
            }
        }).done(function (feedback) {

            var response = JSON.parse(feedback); 
            if(response.status === 1){

                //close header
                $('header').hide();
                closeToolPanel();

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
});
</script>
<div id="receipt-container">
    <div class="form-group">
        <div class="row">
            <div class="col note">
                <p class="m-b-0">Send a receipt for a received payment, such as cash, cheque, or any other means.</p>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row m-b-0">
            <div class="col">
                <?= $label_payment ?>
                <?= $payment ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row m-b-0">
            <div class="col">
                <?= $label_mediumselect ?>
                <?= $mediumselect ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row m-b-0">
            <div class="col">
                <?= $label_receiptmsg ?>
                <?= $receiptmsg ?>
            </div>
        </div>
        <div class="row">
            <div class="col note text-center">
                <span><strong>Note:</strong> If the payment isn't listed, record the payment first</span>
            </div>
        </div>
    </div>
    <div class="form-group bottom-btn-row">
        <div class="row">
            <div class="col">
                <button id="send-receipt" type="button" class="btn btn-success btn-block btn-rounded">
                    <i class="far fa-send"></i>
                    Send Receipt
                </button>
            </div>
        </div>
    </div>
</div>
