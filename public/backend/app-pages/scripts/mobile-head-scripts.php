<?php
use Jenga\App\Request\Url;

require ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'scripts' .DS. 'inline-select2-js.php';
?>
<script type="text/javascript">
function isJson(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}

function CapitalizeString(str) {
    
   var splitStr = str.toLowerCase().split(' ');
   for (var i = 0; i < splitStr.length; i++) {
       // You do not need to check if i is larger than splitStr length, as your for does that for you
       // Assign it back to the array
       splitStr[i] = splitStr[i].charAt(0).toUpperCase() + splitStr[i].substring(1);     
   }
   // Directly return the joined string
   return splitStr.join(' '); 
}
    
function getTodayDate() {
    
   var tdate = new Date();
   var dd = tdate.getDate(); //yields day
   var MM = tdate.getMonth(); //yields month
   var yyyy = tdate.getFullYear(); //yields year
   var currentDate= dd + "-" +( MM+1) + "-" + yyyy;

   return currentDate;
}    

//detect if in viewport
$.fn.isInViewport = function() {
    var elementTop = $(this).offset().top;
    var elementBottom = elementTop + $(this).outerHeight();

    var viewportTop = $(window).scrollTop();
    var viewportBottom = viewportTop + $(window).height();

    return elementBottom > viewportTop && elementTop < viewportBottom;
};

//set the tab button to open follow-up
function setTabButton(page){

    //get the active tab
    var body = $(page + " div.preview-body");
    var tab = body.find('#tabs li.ui-tabs-active a').attr('href');

    var tabbtn = body.find(tab + ' div.tab-btn').html();
    if(tabbtn !== undefined){

        $(page + " div.preview-body").addClass('more-padding');
        $(page + " #tab-btn-holder").html(tabbtn);
    }
}

//check if element is at top
$.fn.isAtTop = function(){
    
    var distance = $(this).offset().top;
    $window = $(window);
    
    if ( $window.scrollTop() >= distance ) {
        return true;
    }
    
    return false;
};

$.fn.closePanelDown = function(){
    
    $(this).animate({'bottom':'-100%', 'top': '100%'},400);
    $(this).css('display', 'block');
    $(this).hide();
};

$.fn.openPanelUp = function(pushToTop = true, bottomHeight = '20px'){
    
    var windowHeight = $(window).height();
    var elemHeight = $(this).height();
    var topHeight = windowHeight - elemHeight;
    
    if(pushToTop === true){
        topHeight = '10vh';
    }
    else if(typeof pushToTop == 'string'){
        topHeight = pushToTop;
    }
    
    $(this).animate({'bottom':bottomHeight, 'top': topHeight},200);
    $(this).css('display', 'block');
    $(this).show();
};

function confirmRefresh(question) {

    if (!$('#dataConfirmModal').length) {

        $('body').append('<div id=\"dataConfirmRefreshModal\" class=\"modal confirm-action fade\" role=\"dialog\" aria-labelledby=\"dataConfirmLabel\" aria-hidden=\"true\"> \
                            <div class=\"modal-dialog\"> \
                                <div class=\"modal-content\"> \
                                    <div class=\"modal-header\"> \
                                        <h3 id=\"dataConfirmLabel\" class="modal-title">Please Confirm Action</h3> \
                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-hidden=\"true\">×</button> \
                                    </div>\n\
                                    <div class=\"modal-body\"></div> \
                                    <div class=\"modal-footer\"> \
                                        <button class=\"btn\" data-dismiss=\"modal\" aria-hidden=\"true\">Cancel</button> \
                                        <a class=\"btn btn-primary\" id=\"refreshProceed\">OK</a> \
                                    </div> \
                                </div> \
                            </div> \
                        </div>');
    }


    $('#dataConfirmRefreshModal').find('.modal-body').html('');
    $('#dataConfirmRefreshModal').find('.modal-body').html('<span class="confirm-txt">' + question + '</span>');
    $('#dataConfirmRefreshModal').modal({show: true});

    return false;
};

function loadBillingInfo(){
    
    $.ajax({
        url: "<?= Url::link('/business/billing/mobile/summary') ?>",
        method: "GET"
    }).done(function(data){
        
        var response = JSON.parse(data);
        var billpage = '#load-bill-page';
        
        if(response.billcount > 0){
            
            //set the counter
            $(billpage + ' span.counter').html(response.billcount);
        
            //show
            if( !$(billpage).hasClass('loaded') ){
                $(billpage).addClass('loaded');
            }
        }
        else{
            //show
            if( $(billpage).hasClass('loaded') ){
                $(billpage).removeClass('loaded');
            }
        }
    });
}

function loadNotificationCount(){
    
    $.ajax({
        url: "<?= Url::link('/business/notices/getcount') ?>",
        method: "GET"
    }).done(function(response){
        
        var noticepage = '#load-notices-page';
        if(response > 0){
            
            //set the counter
            $(noticepage + ' span.counter').html(response);
        
            //show
            if( !$(noticepage).hasClass('priority') ){
                $(noticepage).addClass('priority');
            }
        }
        else{
            
            //show
            if( $(noticepage).hasClass('priority') ){
                $(noticepage).removeClass('priority');
            }
        }
    });
}

function loadDashboardFeatures(){

    //load billing info
    loadBillingInfo();  
    
    //load notifcation count
    loadNotificationCount();
}

$(function(){
    
    /**
     * Load the loading graphic
     * @type type
     */
    $(window).on('load',function() {
        
        $("#transition").fadeOut(750);
        loadDashboardFeatures();
    });
    
    //panel close
    $(document).on('panel:close', function(){
        loadDashboardFeatures();
    });
    
    //remove transition page
    $(document).on( "pageshow", function() {

        //hide transition
        if($('#transition').is(':visible')){
            $("#transition").fadeOut(750);
        }
    });
    
    //load drawer event
    $(document).on('open.drawer', function(event, data){

        //load the event link
        var url = data.url;
        $('#dashboard-overlay').show();

        //start ajax request
        $.ajax({
            method: "GET",
            url: url,
            success: function(response){
                
                $('#dashboard-overlay').hide();
                $('body').prepend(response);
            }
        });

        event.stopPropagation();
    });
    
    $(document).on('jng:dialog:confirmed', function(event, response){
        
        var page = $.mobile.activePage.attr('id');
        //if(page == 'dashboard'){
            
            //decode response
            var response = JSON.parse(response);
            
            //confirm alert
            swal({
                title: response.title,
                text: response.text,
                type: "success",
                button: "Ok",
                width: "530px"
            },function(){
                
                //show transition
                $('#transition').show();
                
                //check reponse action
                if(response.action == 'open' || response.action == 'paused'){
                    
                    //reload page
                    location.reload();
                    return false;
                }
                else{
                    window.location.href = "<?= Url::link('/business/dashboard') ?>";
                }
            });
        //}
    });
    
    $(document).on('jng:close:followup', function(event, response){
    
        swal.close();
        var feedback = JSON.parse(response);
        $('.modal').modal('hide');
        
        //close preview
        var page = $.mobile.activePage.attr('id');
        closeAction(page);
        
        //append response to body
        $('body').append(feedback.summary);
    });
    
    //throw event on overlay click
    $(".overlay").on('click', function(){
        
        var page = $.mobile.activePage.attr('id');
        var event = jQuery.Event('overlay:click');
        
        //set the current page
        event.page = page;
        
        //throw event
        $(document).trigger(event);
    });
    
    //full page refresh
    $('a[data-refresh-page]').on('click', function(event){
        
        event.preventDefault();
        
        //confirm the refresh
        confirmRefresh('This action removes any infomation on the page. Proceed?');
    });
    
    //confirm action which throws refresh:page
    $('body').on('click', '#refreshProceed', function(event){
        
        event.preventDefault();
        var page = $.mobile.activePage.attr('id');
        var event = jQuery.Event('refresh:page');
        
        //set the current page
        event.page = page;
        
        //throw event
        $(document).trigger(event);
        
        //hide all modals
        $('.modal').modal('hide');        
    });
});
</script>
