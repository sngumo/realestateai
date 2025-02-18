<?php
use Jenga\App\Request\Url;
?>
<script type="text/javascript">
function clearForm(form){
    
    form.find(':input').not(':button, :submit, :reset, :hidden, :checkbox, :radio').val('');
    form.find(':checkbox, :radio').prop('checked', false);
}
    
function changePane(page, selector, direction = 'next', focus = true){
    
    var panel = page + ' #create-follow-up-panel';
    
    //check pin to top
    if($(panel).hasClass('fixed') == false){
        
        if($(selector).attr('data-pin-top') === 'true'){
            $(panel).removeAttr('style');
            $(panel + '  div.cf-content').removeAttr('style');
        }
        else{
            $(panel).css('top', 'auto');
        }
    }

    //check for handler 
    if($(selector).attr('data-handler') !== null && typeof $(selector).attr('data-handler') !== 'undefined'){
        
        //execute the handler
        var fxn = $(selector).attr('data-handler');
        window[fxn]($(selector));
    }

    //get and hide previous slide
    $(page + ' div.pane.active').hide('fast');
    $(page + ' div.pane').removeClass('active');        

    //show pane
    $(selector).addClass('active');
    $(selector).show('fast');

    //focus first 
    if($(selector).attr('data-action') !== 'no-focus' && focus === true){
        $(selector + ' input').first().focus();
    }
    
    //check next selector
    var pane = $(selector).attr('data-pane');
    
    //set the next slide in localStorage
    if(pane > 1 && localStorage.getItem('create:followup:mode') !== 'complete'){
        localStorage.setItem('create:followup:mode', pane);
    }
    
    //hide next button
    var current = parseInt(pane);        
    if(localStorage.getItem('create:followup:mode') == 'new'){
        $(page  + ' #pane-buttons .next').hide();
    }
    
    //get next pane
    if(direction === 'next'){
        var next = current + 1;

        //check if next exists
        if($(page + ' [data-pane="' + next + '"]').length == 0){

            $(page + ' #pane-buttons .next').hide();
            $(page + ' #pane-buttons .finish').show();
        }
    }
    else if(direction === 'back'){
        var prev = current - 1;

        //set the prev
        if(prev <= 0){
            prev = 1;
        }
        
        //check if next exists
        if($(page + ' [data-pane="' + prev + '"]').length === 1){

            $(page + ' #pane-buttons .finish').hide();
            $(page + ' #pane-buttons .next').show();
            
            //set the next slide in localStorage
            if(localStorage.getItem('create:followup:mode') !== 'complete'){
                localStorage.setItem('create:followup:mode', prev);
            }
        }
    }
    
    //throw event
    $(document).trigger( "pane:show", ['[data-pane="' + pane + '"]'] );    
}    

function clearPage(page, placeholders){
    
    //show transition
    $('#transition').show();
    
    for (var i = 0, length = placeholders.length; i < length; i++) {
        var tag = placeholders[i];
        var pane = i + 1;
        
        //reset payment purpose
        if(i == 3){
            pane = 2;
        }
        
        //reset sms and phone placeholders
        if(i == 4 || i == 5){
            pane = 1;
        }
        
        if(i == 7){
            pane = 4;
        }
        
        if(i == 8){
            pane = 5;
        }
        
        //return placeholder
        $(page + ' [data-pane-no="' +pane+  '"]').html(tag);
    }
        
    //clear active classname
    $(page + ' div.cf-content div.pane').removeClass('active').hide();
    $(page + ' div.pane .customer-details').hide();

    //clear form inputs
    $(page + ' form[name="quickform"]').trigger("reset");
    
    //clear select2
    $(page + ' #nf_customername').val("");
    
    //reset date
    $(page + ' #due-date-wrapper').show();
    $(page + ' #due-date-display').hide();
    
    //reset reminder pane
    $(page + ' a.delete-reminder-item').trigger('click');
    $(page + ' div.date-select a').removeClass('btn-success picked');
    $(page + ' #reminder-onetime #date-display h3').html('---- -- ----');
    
    $(page + ' div.schedule-panel').removeClass('active').hide();
    $(page + ' #reminder-onetime').show();
    
    //hide payment details
    $(page  + ' #paymentmeans').hide();
    
    //hide buttons
    $(page + ' #pane-buttons .form-group').hide();
    $(page + ' #pane-buttons .form-group.next').show();
    $(page + ' #pane-buttons .form-group .nextslide').show();
    
    //hide tools
    $(page  + ' .app-mobile-logo').show();
    $(page  + ' #create-followup-tools .wrapper').hide();

    //clearlocalStorage
    localStorage.removeItem('create:followup:mode');
}

$(function(){
    
    var page = '#create-follow-up';
    var placeholders = [
        '[ customer-name ]',
        '[ amount-due ]',
        '[ purpose-of-payment ]', 
        '[ due-date ]',
        '[ phone-number ]',
        '[ email-address ]',
        '[ reminder-schedule ]',
        '[ payment-details ]'
    ];
    
    //initialize pane
    $(document).on('pane:show', function(event, pane){

        //set customers list
        if(pane == '[data-pane="1"]'){
            
            if($(pane + ' input').val() == ''){
                
                //inline select2
                $(page + ' #nf_customername').inlineSelect2({
                    source: "<?= Url::link('/business/customers/getinlinelist') ?>",
                    handler: "generateHTMLList",
                    filter: "filterCustomersList",
                    page: page,
                    preloader: "Loading Customers ..."
                });
            }
            else{
                $('#pane-buttons .next').show();
            }
        }

        //set payment details
        if(pane == '[data-pane="2"]'){
        
            var sms = false;
            var email = false;
            var medium = '';
        
            //check sms
            if($(page + ' #mobile').val() !== ''){
                sms = true;
            }

            //set the medium
            if(sms) medium = 'sms';

            //check email
            if($(page + ' #emailaddress').val() !== ''){

                if($(page + ' #mobile').val() !== ''){
                    medium += ':';
                }
                email = true;
            }

            //set email medium
            if(email) medium += 'email';

            //save
            $(page + ' #medium').val(medium);
        
            //inline select2
            $(page + ' #title').inlineSelect2({
                source: "<?= Url::link('/business/invoices/getitemslist') ?>",
                handler: "generatePaymentList",
                filter: "filterPaymentsList",
                page: page,
                preloader: "Loading Payment Items ..."
            });
            
            //hide next button
            if(localStorage.getItem('create:followup:mode') !== 'complete'){
                
                if($(page + ' #title_holder').is(':visible')){
                    $('#pane-buttons .next').hide();
                }
                else{
                    $('#pane-buttons .next').show();
                }
            }
        }

        //set reminders pane
        if(pane == '[data-pane="4"]'){  
        
            //hide next button
            $('#pane-buttons .next').hide();
        }
    });
    
    //on pageshow
    $(page).on('pageshow', function(){
        
        //set the invoice
        var invoice = JSON.parse($(page + ' #invoice_orig').val()); 
    
        //save to local storage for later use
        localStorage.setItem('jng-invoice', JSON.stringify(invoice));
        
        //create followup mode
        if(localStorage.getItem('create:followup:mode') === null 
                || localStorage.getItem('create:followup:mode') === 'complete' ){
            localStorage.setItem('create:followup:mode', 'new');
        }
        
        var active = page + ' .cf-content .pane.active';
        
        //if none is active get pane 1
        if($(active).length == 0){
            active = page + ' div[data-pane="1"]';
        }
        else{
            var pane = $(active).attr('data-pane');
            active = page + ' div[data-pane="' +pane+ '"]';
        }
        
        //check pin to top
        if($(active).attr('data-pin-top') === 'true'){
            $(page + ' #create-follow-up-panel').addClass('fixed');
        }
        
        //show the overlay and panel
        $(page + '-overlay').show();
        $(page + '-panel').addClass('fixed').openPanelUp('0px', '0px');
        
        //activate form button
        var formgroup = page + ' #pane-buttons .form-group';
        
        if(localStorage.getItem('create:followup:mode') != 'complete'){
            
            $(formgroup).hide();
            $(formgroup + '.next').show();
        }
        else{
            
            $(formgroup).hide();
            $(formgroup + '.finish').show();
        }
        
        //show pane
        changePane(page, active);
    });
    
    //on page before hide
    $(page).on('pagebeforehide', function(event){
        
        //remove from localStorage
        localStorage.removeItem('create:followup:mode') ;
    });
    
    //close panel
    $(page + ' .close-panel').on('click', function(){
        
        var mode = localStorage.getItem('create:followup:mode');
        if(mode !== 'complete'){
            $(page + ' a.back-page').trigger('click');
        }
        else{
            //close panel
            $(page + '-panel').closePanelDown();

            //close overlay
            $(page + ' div.overlay').hide();
        }
    });
    
    //on followup completion
    $(page).on('new:followup:complete', function(){
        
        //remove fixed class name
        var panel = page + ' #create-follow-up-panel';
        if($(panel).hasClass('fixed')){
            $(panel).removeClass('fixed');
        }
        
        //close panel
        $(page + ' .close-panel').trigger('click');
        
        //hide logo
        $(page + ' .app-mobile-logo').hide();
        
        //hide last pane
        $(page + ' div[data-pane="6"]').hide();
        
        //show save button & discard button
        $(page + ' #save-followup').removeClass('disabled').addClass('btn-success');
        
        //show draft and buttons
        $(page + ' #draft').show();
        $(page + ' .wrapper').show();
        $(page + ' #save-followup').show();
        $(page + ' #discard-followup').show();
        
        $.bootstrapGrowl("<strong>Confirm the details below.<br/> Tap the words to edit.</strong>", {
            type: "info",
            width: "auto",
            allow_dismiss: true
        });        
    });
    
    //clear page on refresh :page event
    $(document).on('refresh:page', function(event){
        if(page === '#' + event.page){
            clearPage(page, placeholders);
        }
        
         //return to dashboard
        $.mobile.navigate('#dashboard', {
            transition: "slidefade",
            reverse: true
        });
    });
    
    //detect back page
    $(page + ' a.back-page').on('click', function(event){        
        if($(page + ' #nf_customername').val() !== ''){
            jng.confirmActionOnMobile(event, 'The entered informaton may be overwritten. Proceed?', true, 'jng.direct.link', this);
        }
    });
    
    $('body').on('click', '#dataConfirmOK', function(event){
        
        if(page == '#' + $.mobile.activePage.attr('id')){
            
            event.preventDefault();

            var href = $(this).attr('href');
            $('.modal').modal('hide');

            //change page
            $.mobile.navigate(href, {
                transition: "slidefade",
                reverse: true
            });
        }
    });
    
    //save customer name and number
    $(page + ' #save-followup').off('click').on('click', function(){

        jng.blockIO('Saving Follow-Up');

        //save the customer
        var csaveurl = '<?= Url::link('/business/customers/quicksave') ?>';
        var name = $(page + ' #nf_customername').val();
        var no = $(page + ' #mobile').val();
        var email = $(page + ' #emailaddress').val();

        //phone number
        if(no === ''){
            no = 'undefined';
        }

        //email address
        if(email === ''){
            email = 'undefined';
        }

        var inpinvoice = JSON.parse($(page + ' #invoice').val());
        var agencyid = inpinvoice.company.id;

        //save customer via ajax
        $.ajax({
            method: "GET",
            url: csaveurl +'/'+ name +'/'+ no +'/'+ email +'/'+  agencyid
        })
        .done(function(id){

            var invoice = JSON.parse(localStorage.getItem('jng-invoice'));

            //set customer data and id
            invoice.customers_id = id;

            //set the followup title
            invoice.title = $(page + ' #title').val();

            //set the due date
            invoice.due_date = $(page + ' #due_date').val();

            //save to local storage for later use
            localStorage.setItem('jng-invoice', JSON.stringify(invoice));

            //compile and save the invoice rows
            var invoice = JSON.parse(localStorage.getItem('jng-invoice'));

            var row = {};
            var price = $(page + ' div[data-pane="2"] #price').val();
            if(price !== ''){

                row["item"] = $(page + ' #title').val();
                row["quantity"] = 1;
                row["unitrate"] = price;
                row["itemtotal"] = price;

                //now reinsert the row data into the invoice object
                var json = JSON.stringify(row);
                invoice.inv_rows.push(json);

                //set the customer total
                invoice.total = price;

                //save to local storage for later use
                localStorage.setItem('jng-invoice', JSON.stringify(invoice));
            }

            //check the send reminder now
            if($(page + ' #send_reminder_now').is(':checked')){

                var invoice = JSON.parse(localStorage.getItem('jng-invoice'));

                invoice.send_reminder_now = 'yes';
                localStorage.setItem('jng-invoice', JSON.stringify(invoice));
            }

            //add the followup after due date
            if($(page + ' #follow_up_after_yes').is(':checked')){

                var invoice = JSON.parse(localStorage.getItem('jng-invoice'));

                invoice.follow_up_after = 'yes';
                localStorage.setItem('jng-invoice', JSON.stringify(invoice));
            }

            //save invoice object
            var invoice = JSON.parse(localStorage.getItem('jng-invoice'));
            var saveurl = '<?= Url::link('/business/invoices/save') ?>';

            $.ajax({
                method: "POST",
                url: saveurl,
                data: {
                    invoice: JSON.stringify(invoice)
                }
            })
            .done(function(feedback){

                    var response = JSON.parse(feedback);
                    var invresponse = response;
                    
                    //process response
                    if(response.status === 1){

                        //delete localstorage
                        localStorage.removeItem('jng-invoice');
                        localStorage.removeItem('create:followup:mode');

                        //get the checked reminders
                        var reminders = $(page + ' input[name=reminderval]').val();

                        //check if array is empty
                        if(reminders.length === 0){

                            swal({
                                  title: "No reminders selected",
                                  text: 'Please set the remainder schedule',
                                  type: "error",
                                  button: "Ok",
                                  width: "530px"
                                });

                            return false;
                        }

                        //save the remainder schedule
                        $.ajax({
                            method: "POST",
                            url: "<?= Url::link('/business/reminders/quicksave/') ?>" + response.data.id,
                            data: {
                                reminders: JSON.stringify(reminders),
                                startdate: $(page + ' #startdate').val()
                            }
                        }).done(function(status){
                            var response = JSON.parse(status);

                            if(response.status === 1){

                                //check send reminder checkbox
                                if($(page + ' #send_reminder_now').is(':checked')){

                                    $.ajax({
                                        method: "GET",
                                        url: "<?= Url::link('/business/reminders/sendinstant/') ?>" + response.reminder
                                    });
                                }

                                //show complete message
                                swal({
                                    title: invresponse.title,
                                    text: "FollowUp and its reminders saved",
                                    type: "success",
                                    button: "Ok",
                                    width: "530px"
                                }, function(){

                                    $('#transition').show();

                                    //set last follow-up
                                    localStorage.setItem('last-followup', JSON.stringify({
                                        date: getTodayDate(),
                                        id: response.invoice
                                    }));

                                    clearPage(page, placeholders);
                                    
                                     //return to dashboard
                                     window.location.href = "<?= Url::link('/business/dashboard/pluck/') ?>" + response.invoice;
//                                    jQuery.mobile.changePage("<?= Url::link('/business/dashboard/pluck/') ?>" + response.invoice, {
//                                        allowSamePageTransition: true,
//                                        dataUrl: '#dashboard',
//                                        pageContainer: $('#dashboard'),
//                                        showLoadMsg: false,
//                                        transition: 'none',
//                                        reloadPage: true
//                                    });
                                });
                            }
                            else{
                                swal({
                                    title: "Follow-Up not saved",
                                    text: "Follow-Up and its reminders not saved",
                                    type: "error",
                                    button: "Ok",
                                    width: "530px"
                                });
                            }
                        });
                    }
                    else if(response.status === 0){

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
});
</script>
<div id="create-new-followup-container" role="main">
    <div class="app-header">
        <div id="create-followup-tools" class="container p-t-5">
            <div class="row">
                <div class="col-2 app-mobile-logo">
                    <a class="back-page" href="#dashboard" data-transition="slidefade" data-direction="reverse">
                        <img class="logo" src="<?= TEMPLATE_URL ?>/backend/img/followups-icon-small.png" style="width: 40px;" />
                    </a>
                </div>
                <div class="col-2 app-mobile-logo p-l-10">
                  <h1>
                      <span>New</span>
                  </h1>
                </div>
                <div id="discard-followup" class="col float-left p-l-0 wrapper" style="display: none">
                    <a class="btn btn-sm nav-page-btn btn-secondary btn-discard left-align" data-refresh-page>
                        <i class="ti-close close-preview" style="font-weight: bold;"></i>
                        DISCARD
                    </a>
                </div>
                <div class="col p-l-0 p-r-0 wrapper">
                    <a id="save-followup" style="display: none;" class="btn btn-sm nav-page-btn right-align disabled">
                        <i class="fas fa-save"></i>
                        SAVE
                    </a>
                </div>
            </div>
        </div>        
    </div>
    <?php
        $this->loadPanelPosition('mobile-create-followup'); 
    ?>
</div>
