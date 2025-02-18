<?php
use Jenga\App\Views\HTML;
?>
<script type="text/javascript">
    $(function(){
        
        //load drawer event
        $(document).on('open.drawer', function(event, data){
            
            $('div.drawer').fadeIn();
            $('div.drawer-content').delay(100).slideDown();
            
            $('body').addClass('drawer-open').css('padding-right', '15px');
            
            //load the event link
            var url = data.url;
            
            //set the preloader
            $('.drawer-content').html('<?= HTML::AddPreloader('center', '142px','127px', FALSE) ?>');
            
            //start ajax request
            $.ajax({
                method: "GET",
                url: url,
                success: function(response){
                    $('.drawer-content').html(response);
                }
            });
            
            event.stopPropagation();
        });
        
        //close top drawer
        $("body").on('click','a.close-drawer',function(){
            
            $('div.drawer-content').slideUp();
            $('div.drawer').delay(300).fadeOut();
            
            $('body').removeClass('drawer-open').removeAttr('style');
            
            //clear hightlight after close
            var id = $(this).attr('id');
            var idsplit = id.split('|');
            
            var table = idsplit[0];
            var row = idsplit[1];
            
            $('#'+table+' div#'+row).delay(1000).removeClass('highlight');
            $('[id^=shortcutMenu-]').hide(); 
        });
       
        //top-drawer-toggle
        $('a[data-activate="drawer"]').click(function(event){
            
            event.preventDefault();
            $('div.drawer').fadeIn();
            $('div.drawer-content').delay(100).slideDown();
            
            $('body').addClass('drawer-open').css('padding-right', '15px');
            
            //set the table id and row id
            var tableid = $(this).closest('div.gridtable').attr('id');
            var rowid = $(this).closest('div.field-row').attr('id');
             
            //get the panel link
            var link = $(this).find('div.panel-link').attr('data-href');
            if(link === undefined){
                var link = $(this).attr('href');
            }
            
            //set the preloader
            $('.drawer-content').html('<?= HTML::AddPreloader('center', '142px','127px', FALSE) ?>');
            
            //start ajax request
            $.ajax({
                method: "GET",
                url: link,
                success: function(response){
                    
                    $('.drawer-content').html(response);
                    $('.drawer-content').find('a.close-drawer').attr('id', tableid+'|'+rowid);
                    
                    //show hide elements
                    showHideElemenets();
                }
            });
        });
        
        /* Sidebar Show-Hide On Mobile */
        $("a.sidebar-open-button-mobile").on('click', function(ev){

            var sidebar = $('div.sidebar');
            sidebar.toggle("slide", {direction: "left"}, 300);

            if($(this).hasClass('open') === false){

                //add backdrop
                sidebar.after('<div class="dropdown-backdrop canvas"></div>');

                $(this).addClass('open');
                $(this).html('<i class="fa fa-arrow-left"></i>');
                
                //stop the backdrop from being removed later
                ev.stopPropagation();
            }
            else{

                $(this).removeClass('open');
                $(this).html('<i class="fa fa-bars"></i>');
                
                //remove backdrop
                $(document).find('div.dropdown-backdrop').remove();
            }
        });
        
        //close navigation on canvas click
        $('body').on('click', 'div.dropdown-backdrop.canvas', function(ev){
        
            var sidebar = $('div.sidebar');
            sidebar.toggle("slide", {direction: "left"}, 300);
            
            //reset the menu icons
            var icon = $('a.sidebar-open-button-mobile');
            if(icon.hasClass('open') === false){

                //add backdrop
                sidebar.after('<div class="dropdown-backdrop canvas"></div>');

                icon.addClass('open');
                icon.html('<i class="fa fa-arrow-left"></i>');
            }
            else{

                icon.removeClass('open');
                icon.html('<i class="fa fa-bars"></i>');
            }
            
            //remove backdrop
            $(document).find('div.dropdown-backdrop').remove();
            ev.stopPropagation();
        });

        /* Sidebar Show-Hide */    
        $('.sidebar-open-button').on('click', function(){

            $('.sidebar').toggle("slide", {direction: "left"}, 300);
            $(".sidepanel").toggle(100);

            if($('.sidebar').hasClass('hidden')){
                $('.sidebar').removeClass('hidden');
                $('.content').css({
                    'marginLeft' : 250
                });  
            }else{
                $('.sidebar').addClass('hidden');
                $('.content').css({
                    'marginLeft' : 0
                });    
            }
        });
        
        //stop propagation
        $('div.sidebar').on('click', function(ev){
            
            //stop propagation if a direct click
            if(ev.target === this){
                ev.stopPropagation();
            }
        });
    });
</script>
