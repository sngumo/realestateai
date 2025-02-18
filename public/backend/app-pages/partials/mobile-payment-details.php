<script type="text/javascript">

const capitalize = (s) => {
    if (typeof s !== 'string') return '';
    return s.charAt(0).toUpperCase() + s.slice(1);
};

function addPaymentDetails(page){
    
    //show means summary
    $(page + ' .pay-means-summary').show();

    var text = "Pay using ";
    var paymethod = $(page + ' #paymethod').val();

    switch(paymethod){

        case "mpesa":
            var option = $(page + ' #mpesa_options').val();

            if(option === 'paybill'){
                var acno = $(page + ' #paybill').val();
                var acname = $(page + ' #payac').val();

                //create payment type
                text += capitalize(paymethod) + ' Paybill No. <span style="font-weight: bold">' + capitalize(acno) + '</span>'
                        + ' Account Name ' 
                        + '<span style="font-weight: bold">' + acname + '</span>';
            }
            else if(option === 'till'){
                var tillno = $(page + ' #tillno').val();                

                text += capitalize(paymethod) + ' Till No. <span style="font-weight: bold">' + capitalize(tillno)
                        + '</span>';
            }
            else if(option === 'mobile'){
                var mobileno = $(page + ' #mobileno').val();

                text += capitalize(paymethod) + ' Mobile No <span style="font-weight: bold">' + capitalize(mobileno)
                        + '</span>';
            }
            console.log(page, paymethod, option, text);
            break;

        case "mobile":
            text += '<span style="font-weight: bold">' 
                    + capitalize($(page + ' #mobile_provider').val()) + ' ' 
                    + $(page + ' div.mobile-details #mobileno').val() 
                    + '</span>';
          break;

        case "cheque":
            text += capitalize(paymethod)
                    + '<span style="font-weight: bold"> Name ' + $(page + ' #cheque').val() 
                    + '</span>';
            break;

        case "bank":
            text += "Bank Transfer via <span style=\"font-weight: bold\">" 
                        + $(page + ' #bank').val() + "</span> " 
                    + "Account Name <span style=\"font-weight: bold\">" + $(page + ' #bankac').val() + "</span> "
                    + "Number  <span style=\"font-weight: bold\">" + $(page + ' #bankacno').val() + '</span>';
            break;

        default:
            text += '<span style="font-weight: bold">' + capitalize(paymethod) + '</span>';
    }
    
    return text;
}
    
$(function(){
    var page = '#create-follow-up';

    //show or hide payment details div
    $(page + ' .pay-switch').on('click', function(){
        var setup = $(this).attr('for');
        var type = $(page + ' #' + setup).val();

        if(type === 'yes'){
            $(page + ' #paymentmeans').show('fast');
        }
        else if(type === 'no'){
            $(page + ' #paymentmeans').hide('fast');
        }
    });

    //payment instructions
    $(page + ' #paymentmeans div.means').each(function(){
        $(this).hide();
    });

    //activate means div
    var means = $(page + ' #paymethod').val();
    $(page + ' #' + means + '-details').show();

    //check if mpesa
    if(means === 'mpesa'){

        //hide mpesa options
        $(page + ' div.mpesa-optionlist .option').each(function(){
            $(this).hide();
        });

        //display the active one
        var option = $(page + ' #mpesa_options').val();

        if(option !== ''){
            $(page + ' div.' + option).show();
        }
    }

    //load means on change
    $(page + ' #paymethod').on('change', function(){
        var newmeans = $(this).val();

        //hide mpesa options
        $(page + ' div.means').each(function(){

            if($(this).is(':visible')){
                $(this).slideUp();
            }
        });

        //open the new neans div
        $(page + ' #' + newmeans + '-details').slideDown();
    });

    //load mpesa options on change
    $(page + ' #mpesa_options').on('change',function(){

        //close all open options
        $(page + ' div.mpesa-optionlist div.option').each(function(){
            if($(this).is(':visible')){
                $(this).hide();
            }
        });

        var option = $(this).val();
        if(option !== ''){
            $(page + ' div.mpesa-optionlist div.' + option).slideDown();
        }
    });
    
    //set finish button
    $(page + ' .finishslide').on('click', function(){
        
        var payselect = $(page + ' input[name=payment-select]:checked').val();
        if(payselect === 'yes'){

            var text = '';
            text = addPaymentDetails(page);
            
            //add text to div
            $('#draft #pay-means-details').html(text);
        }
        
        //set the next slide in localStorage
        localStorage.setItem('create:followup:mode', 'complete');
        
        //trigger
        $(page).trigger('new:followup:complete');            
    });    
});
</script>
<div class="section-head m-b-20">
    <div class="row">
        <div class="col">
            <h4 class="m-t-0">Do you want to add how you want to be paid?</h4>
        </div>
    </div>
    <div class="row m-b-30">
        <div class="col big-toggle">
            <div class="switch-toggle switch-candy large-9 columns">

              <input type="radio" name="payment-select" checked="checked" id="payment-select-no" data-role="none" value="no" class="control radio remainder setup dont-materilise" checked="checked">                  
              <label for="payment-select-no" id="label_payment-select-no" data-role="none" class="pay-switch active">No</label>

              <input type="radio" name="payment-select" id="payment-select-yes" data-role="none" value="yes" class="control radio remainder setup dont-materilise">                  
              <label for="payment-select-yes" id="label_payment-select-yes" data-role="none" class="pay-switch">Yes</label>
              <a></a>
              
            </div>
        </div>
    </div>
</div>
<div class="form-group" id="paymentmeans" style="display: none;">
    <div class="row m-b-10">
        <div class="col">
            <i class="fas fa-info-circle select"></i>
            <?= $paymethod ?>
        </div>
    </div>
    <div id="mpesa-details" class="means">
        <div class="row m-b-10">
            <div class="col">
                <i class="fas fa-info-circle select"></i>
                <?= $mpesa_options ?>
            </div>
        </div>
        <div class="mpesa-optionlist">
            <div class="row option paybill">
                <div class="col">
                    <i class="fas fa-info-circle"></i>
                    <?= $paybill ?>
                </div>
            </div>
            <div class="row option paybill">
                <div class="col">
                    <i class="fas fa-info-circle"></i>
                    <?= $payac ?>
                </div>
            </div>
            <div class="row option till">
                <div class="col">
                    <i class="fas fa-info-circle"></i>
                    <?= $tillno ?>
                </div>
            </div>
            <div class="row option mobile">
                <div class="col mobileno">
                    <i class="fas fa-info-circle"></i>
                    <?= $mobileno ?>
                </div>
            </div>
        </div>
    </div>
    <div id="mobile-details" class="means">
        <div class="row m-b-10">
            <div class="col">
                <i class="fas fa-info-circle select"></i>
                <?= $mobile_provider ?>
            </div>
        </div>
        <div class="row">
            <div class="col mobile-details">
                <i class="fas fa-info-circle"></i>
                <?= $mobileno ?>
            </div>
        </div>
    </div>
    <div id="bank-details" class="means">
        <div class="row">
            <div class="col">
                <i class="fas fa-info-circle select"></i>
                <?= $bank ?>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <i class="fas fa-info-circle"></i>
                <?= $bankac ?>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <i class="fas fa-info-circle"></i>
                <?= $bankacno ?>
            </div>
        </div>
    </div>
    <div id="cheque-details" class="means">
        <div class="row">
            <div class="col">
                <i class="fas fa-info-circle"></i>
                <?= $cheque ?>
            </div>
        </div>
    </div>
    <div id="othermeans-details" class="means">
        <div class="row">
            <div class="col">
                <i class="fas fa-info-circle"></i>
                <?= $othermeans ?>
            </div>
        </div>
    </div>
</div>