<?php
use Jenga\App\Request\Url;
?>
<script type="text/javascript">
function mapToSummary(page, selector, isValid){

    //check each input 
    $(selector + ' input:not(#s2id_autogen1)').each(function(){

        var valid = $(this).parents('div.col').attr('data-validate');
        var input = $(this).val();
        var id = $(this).attr('id');

        if(valid === 'required' && input === ''){
            $(this).css('border', '1px solid red');

            isValid = false;
        }
        else{

            //map value to summary
            $(page + ' #draft [data-map-to="#' + id + '"]').html(input);
        }
    });
    
    return isValid;    
};

$(function(){
    
    var page = '#create-follow-up';
    
    //return on click
    $('#due_date').on('click', function(){
        
        //initialize due date
        var duedate = $('#due_date').flatpickr({
            minDate: "today",
            dateFormat: "F j, Y",
            onClose: function(selectedDates, dateStr, instance){

                $('#due-date-wrapper').hide('fast');
                $('#due-date-display').show('fast');

                $('#due-date-display span').html('<h5 class="m-t-0 p-t-10" style="border-top: 1px solid grey;">' 
                        + dateStr + '</h5>');
            }
        });
        
        $('#due-date-wrapper').show('fast');
        $('#due-date-display').hide('fast');
        
        duedate.open();
    });
    
    //return on click
    $('#due-date-display').on('click', function(){

        $('#due-date-wrapper').show('fast');
        $('#due-date-display').hide('fast');

        duedate.open();
    });
    
    //open inline select2 on click
    var inlinecontainer = page + ' #nf_customername_holder';
    $(page + ' #customer-list-holder a').on('click', function(){
            
        //show container
        $(inlinecontainer).show('fast');

        //hide customer details
        $(page + ' #customer-list-holder').hide('fast');
        $(page + ' .customer-details').hide('fast');

        //hide next
        var formgroup = page + ' #pane-buttons .form-group';
        $(formgroup + '.next').hide('fast');
    });
    
    //on add new customer
    $(inlinecontainer + ' .button a').on('click', function(){
        
        //hide container
        $(inlinecontainer).hide('fast');
        
        //reset all inputs
        $(page + ' div.customer-pane input[type=text]').val('');
        $(page + ' div.customer-pane input[type=number]').val('');
        
        //show customer details
        $(page + ' .customer-details').show('fast');
        $(page + ' #customer-list-holder').show('fast');
        
        //show next
        var formgroup = page + ' #pane-buttons .form-group';
        $(formgroup + '.next').show('fast');
    });
    
    //get the select2 value
    var pane2container = page + ' #title_holder';
    $(document).on('select2:selected', function(event){
        
        //pane 1 select2
        if(event.id == 'nf_customername_holder'){
            
            //open customer details
            $(inlinecontainer + ' .button a').trigger('click');
            
            //set customer name
            var name = CapitalizeString(event.select2vals[0]);
            $(page + ' #nf_customername').val(name);
            
            //set mobile and email
            var description = JSON.parse(event.select2vals[1]);
            $(page + ' #mobile').val(description.mobile);
            $(page + ' #emailaddress').val(description.email);
        }
        
        //pane 2 select2
        if(event.id == 'title_holder'){
            
            //open payment details
            $(pane2container + ' .button a').trigger('click');
            
            var name = CapitalizeString(event.select2vals[0]);
            $(page + ' #title').val(name);
            $(page + ' #price').val(event.select2vals[1]);
        }
    });
    
    //on add new payments
    $(pane2container + ' .button a').on('click', function(){
        
        //hide container
        $(pane2container).hide('fast');
        
        //reset all inputs
        $(page + ' div.payments-pane input[type=text]').val('');
        $(page + ' div.payments-pane input[type=number]').val('');
        
        //show customer details
        $(page + ' #payment_amount_details').show('fast');
        $(page + ' #payments-list-holder').show('fast');
        
        //show next
        var formgroup = page + ' #pane-buttons .form-group';
        $(formgroup + '.next').show('fast');
    });
    
    //select2 not found
    $(document).on('select2:not-found', function(){
        
        //get active pane
        var activepane = $(page + ' div.cf-content div.pane.active').attr('data-pane');
        
        if(activepane === '1'){
            $(page + ' #nf_customername_holder .button a').trigger('click');
        }
        else if(activepane === '2'){
            $(pane2container + ' .button a').trigger('click');
        }
    });
    
    //open list in payments pane
    $(page + ' #payments-list-holder a').on('click', function(){
            
        //show container
        $(pane2container).show('fast');

        //hide customer details
        $(page + ' #payments-list-holder').hide('fast');
        $(page + ' #payment_amount_details').hide('fast');

        //hide next
        var formgroup = page + ' #pane-buttons .form-group';
        $(formgroup + '.next').hide('fast');
    });
    
    //move to previous slide
    $(page + ' .prevslide').on('click', function(){
        
        var activeid = page + ' div.pane.active';
        var slide = $(activeid).attr('data-pane');
        
        //get previous pane
        var prev = parseInt(slide) - 1;
        
        //reset to one
        if(prev < 1){
            prev = 1;
        }
        
        //set the selector
        var prevSelector = page + ' div.pane[data-pane="' + prev + '"]';

        //change the pane
        changePane(page, prevSelector, 'back');
    });
    
    //move to next slide
    $(page + ' .nextslide').on('click', function(){
        
        var isValid = true;
        var activeid = page + ' div.pane.active';
        var slide = $(activeid).attr('data-pane');
        
        //map to summary
        var valid = mapToSummary(page, activeid, isValid);
        
        if(valid){

            var next = parseInt(slide) + 1;
            var nextSelector = page + ' div.pane[data-pane="' +next+ '"]';

            //chnga the pane
            changePane(page, nextSelector);
        }
    });
    
    //move to next slide on enter button press
    $(document).on('keypress', 'input', function(event) {  
        
        var keycode = event.keyCode || event.which;
        if(keycode == '13') {            
            $(page + ' .nextslide').trigger('click');
        }
    });
    
    //map on save click
    $(page + ' #pane-buttons .saveslide').on('click', function(){
        
        var isValid = true;
        var activeid = page + ' div.pane.active';
        var slide = $(activeid).attr('data-pane');
        
        //map to summary
        var valid = mapToSummary(page, activeid, isValid);
        
        if(valid){
            
            //close panel
            $(page + ' .close-panel').trigger('click');
        }
    });
    
    //pay button
    $(page + ' #pane-buttons .payslide').on('click', function(){
        
        var text = '';
        text = addPaymentDetails(page);
        
        $('#draft #pay-means-details').html(text);
        
        //trigger
        $(page).trigger('new:followup:complete');    
    });
});
</script>
<div class="app-main">
    <?php
        require ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'partials' .DS. 'create-followup-summary.php';
    ?>
</div>
<div id="create-follow-up-overlay" class="overlay" style="display: none;"></div>
<div id="create-follow-up-panel" style="display: none;">
    <div class="cf-heading">
        <div class="row">
            <div class="col-1 text-right m-t-10 m-r-20 p-t-5">
                <a class="close-panel">
                    <i class="ti-close" style="font-weight: bold;"></i>
                </a>
            </div>
            <div class="col text-left m-t-10 p-l-5">
                <div class="title p-t-5">
                    New FollowUp
                </div>
            </div>
            <div class="col-2 p-t-5 p-0 m-r-10">
                <a class="btn btn-sm btn-transparent-outline success prevslide">
                    BACK
                </a>
            </div>
        </div>
        <hr>
    </div>
    <div class="cf-content container-fluid p-l-0 p-r-0">
        <div style="display: none;">
            <?= $follow_up_after_yes ?>
        </div>
        <div class="row">
            <div class="col">
                <div class="pane customer-pane" data-pane="1" data-action="no-focus" style="display: none">
                    <div class="row">
                        <div class="col p-l-20">
                            <h4 class="m-t-0">Set Customer</h4>
                        </div>
                        <div id="customer-list-holder" class="col text-right" style="display: none">
                            <a class="btn btn-light btn-xs">
                                <i class="fa fa-sort-alpha-asc"></i>
                                Open List
                            </a>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row m-b-0">
                            <div id="customer-name" class="col" data-validate="required">
                                <i class="fas fa-user-tie"></i>
                                <?= $nf_customername ?>
                            </div>
                        </div>
                        <div id="nf_customername_holder" class="inline-select2-container">
                            <div class="list m-b-10"></div>
                            <div class="button">
                                <a class="btn btn-block btn-default btn-rounded p-15">
                                    <i class="fas fa-user-plus"></i>
                                    Add New Customer
                                </a>
                            </div>
                        </div>
                        <div class="customer-details" style="display: none;">
                            <div class="row">
                                <div class="col" data-validate="required">
                                    <i class="fas fa-phone-alt"></i>
                                    <?= $mobile ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col" data-validate="optional">
                                    <i class="fas fa-paper-plane"></i>
                                    <?= $emailaddress ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pane payments-pane" data-pane="2" data-action="no-focus" style="display: none">
                    <div class="row">
                        <div id="payments-list-holder" class="col text-right" style="display: none">
                            <a class="btn btn-light btn-xs">
                                <i class="fa fa-sort-alpha-asc"></i>
                                Open List
                            </a>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <div class="col" data-validate="required">
                                <i class="fas fa-info-circle"></i>
                                <?= $title ?>
                            </div>
                        </div>
                        <div id="title_holder" class="inline-select2-container">
                            <div class="list m-b-10"></div>
                            <div class="button">
                                <a class="btn btn-block btn-default btn-rounded p-15">
                                    <i class="fas fa-cart-plus"></i>
                                    Add New Payment Item
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="payment_amount_details" style="display: none;">
                        <div class="row">
                            <div class="col" data-validate="required">
                                <span class="btn btn-sm p-r-5">ksh</span>
                                <?= $price ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pane" data-pane="3" data-action="no-focus" style="display: none">
                    <div class="form-group">
                        <div class="row">
                            <div class="col">
                                <h4 class="m-t-0">Set the due date of payment</h4>
                            </div>
                        </div>
                        <div class="row">
                            <div id="due-date-wrapper" class="col" data-validate="required">
                                <i class="far fa-calendar-times"></i>
                                <?= $due_date ?>
                            </div>
                            <div id="due-date-display" class="col text-center" style="display: none;">
                                <span></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="pane" data-pane="4" data-pin-top="true" data-handler="populateOneTimeDates" data-action="no-focus" style="display: none">
                    <div class="row">
                        <div class="col">
                            <h4 class="m-t-0">Set how the reminders will be sent</h4>
                        </div>
                    </div>
                    <?php
                        require ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'partials' .DS. 'set-reminder.php';
                    ?>
                </div>
                              
                <div class="pane" data-pane="5" data-pin-top="true" data-action="no-focus" style="display:none;">
                    <?php
                        require ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'partials' .DS. 'mobile-payment-details.php';
                    ?>
                </div>
            </div>
        </div>
        <div id="pane-buttons">
            <div class="form-group row next">
                <div class="col">
                    <a id="" class="btn btn-success btn-block nextslide">NEXT</a>
                </div>
            </div>
            <div class="form-group row save" style="display: none">
                <div class="col">
                    <a id="" class="btn btn-success btn-block saveslide">SAVE ENTRY</a>
                </div>
            </div>
            <div class="form-group row pay" style="display: none">
                <div class="col">
                    <a id="" class="btn btn-success btn-block payslide">SAVE PAYMENT</a>
                </div>
            </div>
            <div class="form-group row finish" style="display: none">
                <div class="col">
                    <a id="" class="btn btn-success btn-block finishslide">FINISH</a>
                </div>
            </div>
        </div>
    </div>
</div>