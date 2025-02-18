<?php
use Jenga\App\Request\Url;
?>
<script type="text/javascript">
var tagclass = 'inline-edit';
var section = '.inline-editor-div';
var form = '#inline-edit-form';
    
function showEditorForm(id, text){
    
    $(form + ' input[type="text"]').val(text);
    $(form + ' input[type="text"]').focus();

    $(form + ' input[name=active-element]').val(id);
    $(form + ' input[name=original-value]').val(text);

    //show the editor form
    $(section).show();
}
    
$.fn.inlineEditor2 = function(){
    
    var edit = $(this);
    var section = '.inline-editor-div';
    var inlinehref = {
        customer: "<?= Url::link('/business/customers/save/') ?>",
        title: "<?= Url::link('/business/invoices/change/title/') ?>",
        full_amount: "<?= Url::link('/business/invoices/change/amount_due/') ?>",
        sms: "<?= Url::link('/business/reminder/change/contact/') ?>",
        email: "<?= Url::link('/business/reminder/change/email/') ?>",
        remindertype: "<?= Url::link('/business/reminderonly/') ?>"
    };
    
    //set unique ids
    $('.' + tagclass).each(function(index, value){
        $(this).attr('id', 'inline-edit-element-' + index);
    });
    
    //edit customer name
    var setUpListeners = function(){
        
        edit.on('click', function(){
            
            if(localStorage.getItem('edit-mode') !== null){
                
                //get id
                var id = $(this).attr('id');
                var element = $('#' + id);
                var map = $(this).attr('data-map-to');
                        
                //add clicked html into input
                var html = element.find('span.edit-highlight').html().trim();
                
                switch(map){
                    
                    case "full_amount":                        
                        var amt = html.split(' ');
                        var text = amt[1];
                        
                        //show editor form
                        showEditorForm(id, text);
                        break;
                    
                    case "due_date":
                    case "remindertype":
                        
                        var panel = '#followup-tools-panel';
                        var tool = '#open-followup-container a[data-action="reschedule"]';
                        var title = $(tool).attr('data-panel-title');
                        var thref = $(tool).attr('data-panel-href');
                    
                        if(thref !== undefined){
                            
                            //open panel
                            $(panel).openPanelUp('3vh'); 

                            //set hash
                            window.location.hash = '#tool-open';
                            localStorage.setItem('currentToolHash', '#tool-open');

                            //add title
                            $(panel + ' div.heading div.title').html(title);

                            //add preloader
                            var content = panel + ' div.content';
                            $(content).html('<div style="padding-top: 60px; text-align: center;"> \
                                                        <img src="img/logo.gif" alt=""/> \
                                                    </div>');
                            
                            $.ajax({
                                method: "GET",
                                url: thref
                            }).done(function (response) {
                                $(content).html(response);
                            });
                        }
                    break;
                    
                    default:

                        //show editor form
                        showEditorForm(id, html);
                }
            }
        });

        //cancel action
        $(form + ' .actn-cancel').on('click', function(){
            $(section).hide();
        });

        //enter keypress on mobile
        $(document).on('keypress', form, function(event){

            var keycode = (event.keyCode ? event.keyCode : event.which);
            if(keycode == '13'){

                event.preventDefault();
                
                //trigger save
                $(form + ' .actn-save').trigger('click');
            }
        });

        //save action
        $(form + ' .actn-save').off('click').on('click', function(){

            //block io and disable input
            jng.blockIO('Saving FollowUp');

            var newval = $(form + ' input[name=new-value]').val();
            var active = $(form + ' input[name=active-element]').val();
            var activeelement = $('#' + active);
            var map = activeelement.attr('data-map-to');
            var id = localStorage.getItem('latest-followup');
            
            //get the hidden follwup values
            var hidden = $('#dashboard #contacts-' + id ).val();
            var data = JSON.parse(hidden);
            
            //set id to customer id
            if(map == 'customer'){
                id = data.customerid;
            }
            
            //set the url
            var url = inlinehref[map] + id;
            
            //save customer name
            $.ajax({
                method: "POST",
                url: url,
                data: {
                    newval: newval
                }
            }).done(function(response){

                swal.close();
                $(section).hide();
                
                //reset id
                if(map == 'customer'){
                    id = localStorage.getItem('latest-followup');
                }
                else if(map == 'full_amount'){
                    
                    //add country code
                    var html = activeelement.find('.edit-highlight').html().trim();
                    var amt = html.split(' ');
                    
                    //overwrite response & set the actual amount
                    var actual = parseInt(response);
                    data.amount = actual.toFixed(2);
                    response = amt[0] + ' ' + actual.toFixed(2);                    
                }
                
                //get and update the hidden value
                activeelement.html(response);
                data[map] = response;
                
                //replace the hidden value
                $('#dashboard #contacts-' + id ).val(JSON.stringify(data));
                
                //update dashboard
                if($('#dashboard #' + map +'_'+ id ).length > 0){
                   $('#dashboard #' + map +'_'+ id ).html(response); 
                }
                
                //close edit mode
                $('a.edit-mode').trigger('click');
                
                //show tooltip
                $.bootstrapGrowl("FollowUp updated", {
                    type: "success",
                    width: "auto",
                    allow_dismiss: false
                });
            });
        });
    };
    
    return setUpListeners();
};

$(function(){

    var page = '#follow-up-opened';
    
    //edit mode link
    $('a.edit-mode').on('click', function(){
        
        if(localStorage.getItem('edit-mode') == null){
            
            //set the inline editor
            $('.inline-edit').inlineEditor2();
            
            //set the edit mode in localStorage
            localStorage.setItem('edit-mode', 'ON');

            //set the icons
            $(page + ' .scroll-down a').html('<i class="fas fa-2x fa-chevron-up"></i>');
            $(this).html('<i class="far fa-edit"></i>');

            //show minimized
            $(page + ' .hide-on-minimize').show('fast');

            //add underline
            $('#open-followup-container .inline-edit').each(function(){

                var html = $(this).html();
                $(this).html('<span class="edit-highlight">  ' + html + '  </span>');

                //add padding to due date
                if($(this).hasClass('duedate') && !$(this).hasClass('cost')){
                    $(this).addClass('p-t-20 p-b-20');
                }
                
                //add padding
                if($(this).hasClass('cost')){
                    $(this).addClass('p-t-10 p-b-10');
                }
            });

            //show tooltip
            $.bootstrapGrowl("<strong>Info: </strong> Click the underlined areas to edit", {
                type: "info",
                width: "auto",
                allow_dismiss: false
            });
        }
        else{
            
            //remove edit mode
            localStorage.removeItem('edit-mode');
            
            //set the icons
            $(page + ' .scroll-down a').html('<i class="fas fa-2x fa-chevron-down"></i>');
            $(this).html('<i class="far fa-edit" style="font-weight: bold;"></i>');

            //show minimized
            $(page + ' .hide-on-minimize').hide('fast');
            
            //remove underline
            $('#open-followup-container .inline-edit').each(function(){
                
                //remove the span
                var html = $(this).find('span.edit-highlight').html();
                $(this).html(html);
                
                //remove padding from due date
                if($(this).hasClass('duedate')){
                    $(this).removeClass('p-t-20 p-b-20');
                }
                
                //remove padding
                if($(this).hasClass('cost')){
                    $(this).removeClass('p-t-10 p-b-10');
                }
            });
        }
    });
    
    //show edit tooltip
    $('.inline-edit').on('click', function(){
        
        if(localStorage.getItem('edit-mode') == null){
            
            //show tooltip
            $.bootstrapGrowl("To EDIT, Click the <strong>pen icon</strong> above right", {
                type: "info",
                width: "auto",
                allow_dismiss: false
            });
        }
    });
});
</script>