<?php
use Jenga\App\Request\Input;
?>
<script type="text/javascript">
$(function(){
    
    var tab = 'profile';
    var modal = '#addeditmodal';
    var formid = 'agency-overview';
    
    //load initial tab
    var preload = $('#preloader').html();
  
    //get active tab
    <?php
    if(Input::has('tab')){
    ?>
        //get active link
        var href = $('#'+formid+' li.active a').attr('href');
        tab = $('#'+formid+' li.active a').attr('aria-controls');
    <?php
    }
    else{
    ?>
        //get the active tab from local storage
        if(typeof(localStorage.getItem('agency-active-tab')) !== 'undefined' &&
                localStorage.getItem('agency-active-tab') !== null){
            
            //get the agency tab
            tab = localStorage.getItem('agency-active-tab');
            
            //add active
            $('#'+formid+' a[aria-controls='+tab+']').parent('li').addClass('active');
            var href = $('#'+formid+' li a[aria-controls='+tab+']').attr('href');
        }
        else{
            
            //add active
            $('#'+formid+' a[aria-controls='+tab+']').parent('li').addClass('active');
            tab = $('#'+formid+' li.active a').attr('aria-controls');
        }
    <?php
    }
    ?>
            
    //set preloader
    $('div#' + tab).addClass('active').html(preload);
    
    //check modal size
    if($('#'+formid+' li.active a').data('modal-size') === 'normal'){
        $(modal + ' div.modal-dialog').removeClass('modal-lg');
    }
    else if($('#'+formid+' li.active a').data('modal-size') === 'large'){
        $(modal + ' div.modal-dialog').addClass('modal-lg');
    }
    
    //load the necessary form
    if(typeof(href) !== 'undefined'){
        $.ajax({
            url: href,
            method: "GET"
        })
        .done(function(data){
            $('div#' + tab).html(data);
            localStorage.setItem('agency-active-tab', tab);
        });
    }
    
    //add click event handler
    $('ul.nav-tabs a').on('click', {preloader: preload,modal: modal},
    
        function(event){
        
            event.preventDefault();
            
            //remove previous active link and tab
            $('ul.nav-tabs li.active').removeClass('active');
            $('ul.nav-tabs li a.active').removeClass('active');
            $('div.tab-content div.active').removeClass('active');

            //check modal size
            if($(this).data('modal-size') === 'normal'){
                $(event.data.modal+' div.modal-dialog').removeClass('modal-lg');
            }
            else if($(this).data('modal-size') === 'large'){
                $(event.data.modal+' div.modal-dialog').addClass('modal-lg');
            }

            //get active tab
            var href = $(this).attr('href');
            var tab = $(this).attr('aria-controls');
            
            //set the active tab
            localStorage.setItem('agency-active-tab', tab);

            $(this).parent('li').addClass('active');
            $('div#' + tab).addClass('active').html(event.data.preloader);
            
            //check disable-tab
            if($(this).hasClass('disable-tab') || $(this).hasClass('disable-main-tab')){
                return;
            }

            //disable all other links while loading
            $(document).find('#'+formid+' li a').not(this).addClass('disable-tab');
            $(document).find('#'+formid+' li a').not(this).addClass('disabled');
            
            //load tab content
            $.ajax({
                url: href,
                method: "GET"
            })
            .done(function(data){
                $('div#' + tab).addClass('active').html(data);
                
                //restore after loading
                $(document).find('#'+formid+' li a').not(this).removeClass('disable-tab');
                $(document).find('#'+formid+' li a').not(this).removeClass('disabled');
            });
        });
    });
</script>