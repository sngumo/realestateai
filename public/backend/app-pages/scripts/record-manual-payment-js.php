<?php
use Jenga\App\Request\Url;
?>
<script type="text/javascript">
        
    var pane = '#followup-tools-panel #manualpayment';
        
    function buildFields(){
        
        var pane = '#followup-tools-panel #manualpayment';
        var container = $(pane + ' #fields-container');
        
        //clear container
        container.html('');
        
        //get the hidden values
        $(pane + ' .form-group input, ' + pane + ' .form-group select').each(function(){

            var value = $(this).val();
            var formgroup = $(this).parents('.form-group');
            
            if(value !== ''){
                var parentid = formgroup.attr('id');
                var fieldname = formgroup.attr('data-field-name');
                var amount = formgroup.attr('data-min-val');
                
                //check amount
                if(amount != undefined){
                    
                    //check minimum values
                    var amtint = parseInt(amount);
                    var valint = parseInt(value);
                    
                    if(valint < amtint){
                        $('span.notice').remove();
                        
                        $(this).css('border', '1px solid red');
                        $('<span class="notice" style="color: red; font-weight: 400;">Amount must be above '+ amount +'</span>').insertAfter($(this));
                        
                        return false;
                    }
                    else{
                        
                        if(valint >= amtint){
                            $('#savebtn-partial').removeClass('disabled');
                        }
                    }
                }
                
                var holder = '<div class="field" data-form-id="#'+ parentid +'"> \
                                <strong>'+ fieldname +': </strong>' + value + '</div>';

                //add to container
                container.prepend(holder);
                
                //add hidden class
                if(!formgroup.hasClass('hidden')){
                    formgroup.addClass('hidden');
                }
            }
        });
    }
    
    function loadBillingInfo(){
            
        var inv = $('#invoice_id').val();
        var amt = $('#amount').val();     
        var date = $('#paydate').val();
        var type = $('#method').val();
        var transactiontype = type.substr(0,1).toUpperCase() + type.substr(1);
        transactiontype = 'Payment <br/>via ' + transactiontype;

        var total = parseFloat($('#fup_amount').val());
        var balance = total - parseFloat(amt);

        //decode the charges
        var charges = $('#billing_charges').val();
        charge = JSON.parse(charges);
        
        //set remaining balance
        var page = $.mobile.activePage.attr('id');
        
        //get the hidden followup div
        var hidden = $('#dashboard #contacts-' + inv ).val();
        var data = JSON.parse(hidden);
        
        //replace the due_date
        var full_amount = charge.currency+' '+balance.toFixed(2);
        data.full_amount = full_amount;
        data.amount = balance.toFixed(2);
        
        //replace the hidden value
        $('#dashboard #contacts-' + inv ).val(JSON.stringify(data));
        $('#dashboard #full_amount_'+ inv ).html(full_amount);
        
        if(page == 'dashboard'){            
            $('#preview-body div[data-map-to="full_amount"]').html(full_amount);
        }
        else if(page == 'follow-up-opened'){
            $('#open-followup-container div[data-map-to="full_amount"]').html(full_amount);
        }
        
        var reminder = '';
        var page = '\<div class="line row p-b-0 p-t-0"> \
                          <div class="col text-left"> \
                            <h6 id="summary-due-date" class="m-t-0 no-bold">'+date+'</h6> \
                          </div> \
                      </div>\
                      <div class="line row p-b-20 p-t-10"> \
                          <div class="col text-center"> \
                            <h4>CUSTOMER</h4> \
                            <h4 class="summary-customer-name m-t-0 no-bold">'+charge.customer+'</h4> \
                          </div> \
                      </div> \
                    '+ reminder +' \
                    <div class="row"> \
                        <div class="col"> \
                            <table class="table debt-items m-t-0"> \
                              <tbody> \
                                <tr> \
                                    <td>Total Amount</td><td class="text-right"><strong>'+charge.currency+' '+total.toFixed(2)+'</strong></td> \
                                </tr> \
                                <tr> \
                                    <td>' +transactiontype+ '</td><td class="text-right"><strong>'+charge.currency+' '+charge.payment+'</strong></td> \
                                </tr> \
                                <tr> \
                                    <td colspan="2" class="text-right">Administration Fee <br/><strong>'+ charge.currency +' '+ charge.fee +'</strong></td> \
                                </tr> \
                                <tr> \
                                    <td colspan="2" class="text-right" style="color: red">Remaining Balance <br/><strong>'+charge.currency+' '+balance.toFixed(2)+'</strong></td> \
                                </tr> \
                              </tbody> \
                            </table> \
                        </div> \
                    </div>';

        $(pane  + ' #panel-1').hide();
        $(pane  + ' #panel-2 .section-body').show();
        
        //insert the charges
        $(pane  + ' #billing-details').html(page);

        //show send receipt
        $(pane  + ' #sendreceipt').show();
    }
    
    function checkIfValid(id, selector, isValid = true){

        //check each input 
        $(id +' '+ selector).each(function(){

            var valid = $(this).attr('data-validate');
            var input = $(this).find('input');
            var select = $(this).find('select');
            
            if(valid === 'required'){
                
                if(input.length >= 1 && input.val() == ''){
                    $(this).css('border-bottom', '1px solid red');
                    isValid = false;
                }
                else if(select.length >= 1 && select.val() == ''){
                    $(this).css('border-bottom', '1px solid red');
                    isValid = false;
                }
            }
            
            if(isValid === false){
                
                //check if input is hidden
                var parent = $(this).parents('.input-holder');
                if(parent.is(':visible') === false){
                    parent.show();
                }
            }
        });
        
        return isValid;    
    };
    
    $(function(){
        
        //build fields
        buildFields(pane);
        
        //open hidden field on click
        $(document)
                .off('click', pane + ' #fields-container .field')
                .on('click', pane + ' #fields-container .field', function(event){
            
            event.stopPropagation();
            
            var field = $(this).attr('data-form-id');
            var location = pane + ' ' +field;
            
            if($(location).hasClass('hidden')){
                
                //show input group
                $(location).removeClass('hidden');
                $('[data-form-id="'+ field +'"]').addClass('active');
                
                //get input and check date
                var input = $(location + ' input');
                if(input.attr('id') == 'paydate'){
                    $(pane + ' #paydate').trigger('click');
                }  
            }
            else{
                $(location).addClass('hidden');
                
                if($('[data-form-id="'+ field +'"]').hasClass('active')){
                    $('[data-form-id="'+ field +'"]').removeClass('active');
                }
            }
        });
        
        //rebuild fields
        $(pane + ' .form-group input, ' + pane + ' .form-group textarea, ' + pane + ' .form-group select').on('focusout', buildFields);
        
        //enable record on keyup
        $(pane + ' .form-group input#amount').on('keyup', function(){
            
            var savebtn = $('#savebtn-partial');
            
            if(savebtn.hasClass('disabled')){
                savebtn.removeClass('disabled');
            }
        });
        
        //open on click
        $(pane + ' #paydate').on('click', function(){
            
            //initialize pay date
            var paydate = $(pane + ' #paydate').flatpickr({
                maxDate: "today",
                dateFormat: "l, j F Y",
                onChange: function(selectedDates, dateStr, instance){
                    
                    if(selectedDates.length == 0){
                        
                        //set the value and change the type
                        $(pane + ' #paydate').val(dateStr);
                        $(pane + ' #paydate').attr('type', 'text');
                        
                        //destroy instance
                        instance.destroy();
                    }
                },
                onClose: function(selectedDates, dateStr, instance){

                    if(selectedDates.length !== 0){
                        
                        //set the value and change the type
                        $(pane + ' #paydate').val(dateStr);
                        $(pane + ' #paydate').attr('type', 'text');
                        
                        //destroy instance
                        instance.destroy();
                        
                        //rebuild fields
                        buildFields();
                    }
                },
                onDestroy: function(selectedDates, dateStr, instance){
                    
                    if(selectedDates.length == 0){
                        
                        //set the value and change the type
                        $(pane + ' #paydate').val(dateStr);
                        $(pane + ' #paydate').attr('type', 'text');
                        
                        //destroy instance
                        instance.destroy();
                    }
                }
            });
            
            //open date picker
            paydate.open();
        });
        
        //save partial button
        $('#savebtn-partial').on('click', function(event){

            event.preventDefault();
            
            //check disabled class
            if($(this).hasClass('disabled')){
                return false;
            }
            
            var url = $("form#manualpayment").attr('action');
            
            $(this).addClass('disabled');
            
            //check if disabled
            if(checkIfValid('form#manualpayment','div[data-validate]')){
                
                var inv = $('#invoice_id').val();
                var amt = $('#amount').val();  
                var agenciesid = $('#agencies_id').val();
                
                //info
                $(this).html('Saving Payment');
                $('header').show();
                
                $.bootstrapGrowl('Recording Payment', {
                    type: "info",
                    align: "center",
                    offset: {from: 'top', amount: 50},
                    delay: 15000,
                    allow_dismiss: true
                });
            
                //compile payment details
                var payments = [];
                $(pane+' input, '+pane+' select').each(function(){
                    var input = $(this).val();
                    payments.push(input);
                });                
            
                $.ajax({
                    method: "GET",
                    url: url +'/'+ inv + '/' + amt,
                    success: function(charges){

                        var api = $('#from_api').val();  
                        if(api != 'yes'){
                            var savepartialurl = "<?= Url::link('/business/payments/savepartial') ?>";
                        }
                        else{
                            var savepartialurl = "<?= Url::link('/api/business/payments/savepartial/') ?>" + agenciesid;
                        }

                        //add billing charges to hidden input
                        $('#billing_charges').val(charges);

                        //save partial payment
                        $.ajax({
                            method: "POST",
                            url: savepartialurl,
                            data: {
                                payments: JSON.stringify(payments),
                                charges: $('#billing_charges').val(),
                                sendreceipt: $('#on_due').val(),
                                invoice: $('#invoice_id').val(),
                                currency: $('#currencies_id').val(),
                                customers_id: $('#customers_id').val(),
                                api: api
                            }
                        })
                        .done(function(feedback){
                            
                            var response = JSON.parse(feedback);
                    
                            if(response.status === 1){

                                $('div.bootstrap-growl').remove();

                                //load billing info
                                loadBillingInfo();

                                //increase the height of the content section
                                $('#followup-tools-panel div.content').css('height', '85vh');

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
                            else if(response.status == 2){
                                
                                var userdata = JSON.parse(localStorage.getItem('user-data')); 
                                
                                $.ajax({
                                    url: "<?= Url::link('/api/business/invoices/closesummary/') ?>" + response.invoice + '/' + userdata.agenciesid,
                                    method: "GET",
                                    cache: false,
                                    success: function (fdb) {
                                        
                                        var rsp = JSON.parse(fdb);
                                        $('.modal').modal('hide');

                                        //append response to body
                                        $('body').append(rsp.summary);
                                        
                                        $('header').hide();
                                        closeToolPanel();
                                        
                                        //close preview box
                                        $('#preview-box .ti-close').trigger('click');
                                        
                                        //set the tool refresh data
                                        var refreshdata = {
                                            action: 'refresh',
                                            page: '#dashboard',
                                            updated: true
                                        };
                                        
                                        localStorage.setItem('tool:refresh:data', JSON.stringify(refreshdata));
                                    }
                                });
                            }
                        });
                    }
                });
            }
        });
        
        //close summary
        $('#exit-summary-box .close-summary').on('click', function(){
            $('#panel-2').hide('fast');

            //reload page
            $('#transition').show();

            var page = $.mobile.activePage.attr('id');
            if(page == 'dashboard'){
                
                //close tools panel
                closeToolPanel(true);
                
                //refresh filter toolbar
                refreshFilterToolbar('#dashboard');
            }
            else{
                location.reload();
            }
        });
    });
</script>
