<script type="text/javascript">
    
    var pane = '#followup-tools-panel #remainderform';
        
    function buildFields(){
        
        var pane = '#followup-tools-panel #remainderform';
        var container = $(pane + ' #fields-container');
        
        //clear container
        container.html('');
        
        //get the hidden values
        $(pane + ' .form-group input').each(function(){

            var value = $(this).val();
            var formgroup = $(this).parents('.form-group');
            
            if(value !== ''){
                var parentid = formgroup.attr('id');
                var holder = '<div class="field" data-form-id="#'+ parentid +'">' + value + '</div>';

                //add to container
                container.prepend(holder);
            }
                
            //add hidden class
            if(!formgroup.hasClass('hidden')){
                formgroup.addClass('hidden');
            }
        });
    }
    
    $(function(){
        
        buildFields(pane);
        
        //open hidden field on click
        $(document)
                .off('click', pane + ' #fields-container .field')
                .on('click', pane + ' #fields-container .field',function(event){
                    
            var field = $(this).attr('data-form-id');
            var location = pane + ' ' +field;
            
            event.stopPropagation();
            
            if($(location).hasClass('hidden')){
                $(location).removeClass('hidden');
                $('[data-form-id="'+ field +'"]').addClass('active');
            }
            else{
                $(location).addClass('hidden');
                
                if($('[data-form-id="'+ field +'"]').hasClass('active')){
                    $('[data-form-id="'+ field +'"]').removeClass('active');
                }
            }
        });
        
        //rebuild fields
        $(pane + ' .form-group textarea').on('focus', buildFields);
        
        $('#clickable-div').on('click', function(event){
            
            //check text count
            var textcount = localStorage.getItem('text-count');
            if(textcount !== null){
                
                if(textcount === 'invalid'){
                    $.bootstrapGrowl('The message cannot exceed 320 chars', {
                        type: "error",
                        align: "center",
                        offset: {from: 'bottom', amount: 70},
                        delay: 4000,
                        allow_dismiss: true
                    });
                }
            }            
        });
        
        //on click send reminder
        $('#send-reminder').on('click', function(event){
            
            event.preventDefault();
            
            //validate inputs
            var msg = $(pane + ' .form-group textarea').val();
            if(msg == ''){
                $(pane + ' .form-group textarea').css('border-bottom', '5px solid red');
                return false;
            }
            
            $(this).addClass('disabled');
            
            $('header').show();
            $.bootstrapGrowl('Sending Reminder', {
                type: "info",
                align: "center",
                offset: {from: 'top', amount: 50},
                delay: 15000,
                allow_dismiss: true
            });
            
            var form = $('#remainderform');
            var data = form.serializeArray();
            var action = form.attr('action');
            var method = form.attr('method');
            
            $.ajax({
                url: action,
                method: method,
                cache: false,
                data: data
            }).done(function (feedback) {

                $('#send-reminder').removeClass('disabled');
                $('#send-reminder').html('Send Message');
                
                var response = JSON.parse(feedback);
                if(response.status === 1){

                    //show tooltip
                    $('div.bootstrap-growl').remove();
                    $.bootstrapGrowl(response.title, {
                        type: "success",
                        align: "center",
                        offset: {from: 'top', amount: 50},
                        delay: 10000,
                        allow_dismiss: true
                    });
                    
                    //activate refresh
                    activateRefresh();
                    
                    //hide 
                    $('header').hide();
                    closeToolPanel();
                }
                else if(response.status === 0){

                    //error message
                    $.bootstrapGrowl(response.title, {
                        type: "danger",
                        align: "center",
                        offset: {from: 'top', amount: 50},
                        delay: 15000,
                        allow_dismiss: true
                    });
                }
            });
        });
        
        <?php
        if($is_daily_reminder_valid){
        ?>
                
            //get the messages count in the text area
            var rform = '#remainderform ';
            var textcount = $(rform + '#message').val();
            var exactcount = rform + '#exact-count';
            var pcount = rform + '#char-count';

            //compare text count
            function charCount(textcount){

                //set the text count
                $(exactcount).html(textcount.length);

                if(textcount.length > 320){

                    //change text to red
                    $(pcount).removeClass('green').addClass('red');

                    //disable send button
                    $(rform + '#send-reminder').addClass('disabled');
                    $(rform + '#clickable-div').removeClass().addClass('active');

                    localStorage.setItem('text-count', 'invalid');
                }
                else{
                    //change text to green
                    $(pcount).removeClass('red').addClass('green');

                    //disable send button
                    $(rform + '#send-reminder').removeClass('disabled');
                    $(rform + '#clickable-div').removeClass().addClass('inactive');

                    localStorage.setItem('text-count', 'valid');
                }
            }

            //load function
            charCount(textcount);

            //change count on keyup
            $(rform + '#message').on('keyup', function(){
                var current = $(this).val();

                //run count
                charCount(current);
            });
            
        <?php
        }
        ?>
    });
</script>
<div id="fields-container"></div>
<div id="reminder-input-2" class="form-group email m-t-5 hidden">
    <div class="row input-holder">
        <div class="col">
            <?= $email ?>
        </div>
    </div>
</div>
<div id="reminder-input-1" class="form-group phone-number m-t-5 hidden">
    <div class="row input-holder">
        <div class="col">
            <?= $contact ?>                
        </div>
    </div>
</div>
<?php
if($is_daily_reminder_valid){
?>
    <div id="text-char-count" class="row m-t-10">
        <div class="col text-left">
            <p id="char-count" class="field">Text: <span id="exact-count">320</span></p>
        </div>
        <div class="col text-right">
            <p class="field">Max Text Count: 320</p>
        </div>
    </div>
    <div id="input-3" class="form-group message">
        <div class="row input-holder">
            <div class="col">
                <?= $message ?>                
            </div>
        </div>
    </div>
    <div class="form-group bottom-btn-row">
        <div class="row">
            <div class="col">
                <button id="send-reminder" type="button" class="btn btn-success btn-block btn-rounded">
                    <i class="far fa-send"></i>
                    Send Message
                </button>
                <div id="clickable-div" class="active"></div>
            </div>
        </div>
    </div>
<?php
}
else{
?>

        <div id="text-char-count" class="row">
            <div class="col text-center">
                <h2  id="char-count" class="field red" style="float: none; margin-left: auto; margin-right: auto;">
                    Daily Limit Reached
                </h2>
                <p>Only one reminder can be sent per day</p>
            </div>
        </div>

<?php
}
?>
<style>
    #clickable-div.active{
        position: absolute;
        bottom: 0px;
        width: 352px;
        height: 52px;
    }
    #clickable-div.inactive{
        position: absolute;
        bottom: 0px;
        width: 0px;
        height: 0px;
    }
    #text-char-count .field{
        border-radius: 17px;
        padding: 5px;
        width: auto;
        font-weight: bold;
        padding-left: 12px;
        padding-right: 12px;
        display: table;
        float: left;
        margin-right: 5px;
        font-size: 15px;
        height: 30px;
        white-space: nowrap;
        border: 2px solid lightgrey;
    }
    #text-char-count .field.red{
            color: white;
            border: 2px solid lightcoral;
            background-color: red;
    }
    #text-char-count .field.green{
            color: white;
            border: 2px solid lightgreen;
            background-color: green;
    }
    
</style>
