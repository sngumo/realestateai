/* Sidebar Menu*/
function loadAjaxData(href){
    
    if(href !== '#'){
        
        var preload = $('div.preload').html();
        $('div.right-details-panel').html(preload);
        
        //start ajax request
        $.ajax({
            method: "GET",
            url: href,
            success: function(response){
                $('div.right-details-panel').html(response);
            }
        });
    }
}

$(document).ready(function () {
    
   //open main menu 
   if($('body').attr('environment') === 'mobile'){
       
       //open child submenu
       $('.nav > li > a').click(function(ev){

            if ($(this).parent('li').hasClass('has-submenu')){     

                $(this).parent('li.has-submenu').children('ul.submenu').toggle("slide", {direction: "right"}, 20);

                //add class
                $('div.sidebar').addClass('open');
                $('.nav li a').removeClass('active');
                $(this).addClass('active');
                
                //stop propagation
                ev.stopPropagation();
            }
        });
        
        //close child submenu
        $('ul.child > div.submenu-title').on('click', function(ev){
            
            $('.nav li a').removeClass('active');
            $(this).parent('ul.child').toggle("slide", {direction: "right"}, 20);
            
            //stop propagation
            ev.stopPropagation();
        });
   }
   else{
       
        $('.nav > li > a').click(function(){

            if ($(this).parent('li').hasClass('has-submenu')){     

                $(this).parent('li.has-submenu').children('ul.submenu').toggle("slide", {direction: "left"}, 20);

                $('.nav li a').removeClass('active');
                $(this).addClass('active');
            }
        });

        /* Sidebar Menu Close */
        $('ul.submenu h4 i').on('click', function(){
            $('.nav li ul').toggle("slide", {direction: "left"}, 20);
            $('.nav li a').removeClass('active');
        });


        /**
        * On notifications link click
        */
        $('div.sidebar-right > div.notifications a').on('click', 

        /**
         * Rightbar click
         * @param {type} event
         * @returns {undefined}
         */
        function(event){

          event.preventDefault();

          if($(this).parent('div.notifications').hasClass('active')){

              //deactivate active notications tray
              $(this).parent('div.notifications').removeClass('active');
              $('div.sidebar-right-panel').toggle("slide", {direction: "right"}, 20);
          }
          else{

              //remove previous notifications
              $('div.sidebar-right > div.notifications').removeClass('active');

              //add new class
              $(this).parent('div.notifications').toggleClass('active');

              //check active class
              if($('div.sidebar-right-panel').is(":visible") === false){
                  $('div.sidebar-right-panel').toggle("slide", {direction: "right"}, 20);
              }

              //show/hide login div
              if($(this).attr('id') === 'login-icon'){

                  if($('div.login-panel').is(":visible") === false){
                      $('div.login-panel').show();
                      $('div.right-details-panel').hide();
                  }
              }
              else{
                  $('div.login-panel').hide();
                  $('div.right-details-panel').show();
              }

              var href = $(this).attr('href');
              loadAjaxData(href);
          }
        });
    }
});

//click outside
$('div.content').click(function(event) { 
    if(!$(event.target).closest('ul.submenu').length) {
        
        //close submenu
        if($('ul.submenu').is(":visible")) {
            $('.nav li a').removeClass('active');
            $('ul.submenu').hide();
        }
    }        
    
    //close right bar panel
    if($('div.sidepanel').is(":visible")){
        $('div.sidepanel').hide();
    }
});

//close sidebar
$('.profilebox').on('click',function(){ 
    if($('div.sidepanel').is(":visible")){
        $('div.sidepanel').hide();
    }
});

/* ===========================================================
PANEL TOOLS
===========================================================*/
/* Minimize */
$(document).ready(function(){
  $(".minimise-tool").click(function(event){
  $(this).parents(".panel").find(".panel-body").slideToggle(100);

  return false;
}); 

 }); 

/* Close */
$(document).ready(function(){
    $(".panel-tools .closed-tool").click(function(event){
      $(this).parents(".panel").fadeToggle(400);

      return false;
    }); 
 }); 

 /* Search */
$(document).ready(function(){
      $(".panel-tools .search-tool").click(function(event){
          $(this).parents(".panel").find(".panel-search").toggle(100);
          return false;
      }); 
 }); 

/* expand */
$(document).ready(function(){

    $('.panel-tools .expand-tool').on('click', function(){
        if($(this).parents(".panel").hasClass('panel-fullsize'))
        {
            $(this).parents(".panel").removeClass('panel-fullsize');
        }
        else
        {
            $(this).parents(".panel").addClass('panel-fullsize');
 
        }
    });

});


/* ===========================================================
Widget Tools
===========================================================*/


/* Close */
$(document).ready(function(){
  $(".widget-tools .closed-tool").click(function(event){
  $(this).parents(".widget").fadeToggle(400);

  return false;
}); 

 }); 


/* expand */
$(document).ready(function(){

    $('.widget-tools .expand-tool').on('click', function(){
        if($(this).parents(".widget").hasClass('widget-fullsize'))
        {
            $(this).parents(".widget").removeClass('widget-fullsize');
        }
        else
        {
            $(this).parents(".widget").addClass('widget-fullsize');
 
        }
    });

});

/* Tooltips */
$(function () {
  $('[data-toggle="tooltip"]').tooltip();
});

/* Popover */
$(function () {
  $('[data-toggle="popover"]').popover();
});

/* Update Fixed */
/* Version 1.2 */

/**
 * Load the loading graphic
 * @type type
 */
$(window).on('load',function() {
    $(".loading").fadeOut(750);
});
