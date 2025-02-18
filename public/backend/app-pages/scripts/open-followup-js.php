<?php
use Jenga\App\Request\Url;
?>

<!--Load Notifications-->
<script src="<?=RELATIVE_VIEWS ?>/notifications/notifications.js"></script>

<?php
require_once ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'scripts' .DS. 'inline-editor-js.php';
?>
<script type="text/javascript">
$(function () {
    var page = '#follow-up-opened';
    
    function initPage(id, page){        
     
        if(id !== null && $(document).find('#dashboard #contacts-' + id).length > 0){       
            
            //check edit mode
            if(localStorage.getItem('edit-mode') !== null){
               localStorage.removeItem('edit-mode'); 
            }
            
            //get the contacts
            var contacts = JSON.parse($('#contacts-' + id).val());

            //add status to follow-up
            $(page + " div.preview-header").removeClass()
                    .addClass('preview-header')
                    .addClass(contacts.status);

            $(page + " div.preview-body").removeClass()
                    .addClass('preview-body')
                    .addClass(contacts.status);

            //also the floating box
            $(page + " div.floating-box").removeClass()
                    .addClass('floating-box')
                    .addClass(contacts.status);

            //also the back-btn
            $(page + " a.back-btn").removeClass()
                    .addClass('back-btn')
                    .addClass(contacts.status);
            
            $(page + " .scroll-down a").removeClass().addClass('m-t-10').addClass(contacts.status);

            //map value to element
            for(var key in contacts){
                $(page + " [data-map-to=" + key + "]").html(contacts[key]);
            }
        }
        else{

            //change page
            $.mobile.navigate('#dashboard', {
              transition: "none"
            });
            
            //show tooltip
            $.bootstrapGrowl("Error loading follow up. Pull down to refresh page", {
                type: "danger",
                width: "auto",
                allow_dismiss: false
            });
        }
    }
    
    //on pageshow
    $(page).on('pageshow', function(){
        
        //initialize page
        var classname = $(this).find('.preview-header').attr('class');
            
        var id = localStorage.getItem('latest-followup');
        initPage(id, page);

        //load the followup tabs
        if(id !== null){              
            $(page + " div.preview-body")
                            .html('<div style="padding-top: 60px; text-align: center;"> \
                                    <img src="<?= TEMPLATE_URL ?>/backend/img/logo.gif" alt=""/> \
                                    </div>');

            $.ajax({
                method: "GET",
                url: "<?= Url::link('/business/invoices/quick/open/') ?>" + id,
                error: function () {
                    $(document).trigger('connection:error');
                }
            }).done(function(response){

                //get and set tabs
                $(page + " div.preview-body").html(response);
                $(page + " div.preview-body").find('#tabs').tabs();

                //set the tab button
                setTabButton(page);
            });
        }
    });
    
    //hide sections on click
    var scroller = page + ' .scroll-down a';
    $(scroller).on('click', function(){
        
        if($(scroller + ' i').hasClass('fa-chevron-down')){
            
            $(page + ' .hide-on-minimize').show('fast');
            $(this).html('<i class="fas fa-2x fa-chevron-up"></i>');
        }
        else{
            
            $(page + ' .hide-on-minimize').hide('fast');
            $(this).html('<i class="fas fa-2x fa-chevron-down"></i>');
        }
        
        //scroll to top
        $(page + ' #customer-and-status')[0].scrollIntoView();
    });
    
    //on page hide show all hidden sections
    $(page).on('pagehide', function(){
        
        $(page + ' .hide-on-minimize').hide('fast');
        $(page + ' #tab-btn-holder').html('');
        $(page + ' .preview-footer').hide('fast');
        $(page + " div.preview-body").html('');
        $(page + ' div.floating-box div.notice').html("");
        
        $(scroller + ' i').removeClass().addClass('fas fa-2x fa-chevron-down');
        
        //edit mode link
        if(localStorage.getItem('edit-mode') !== null){
            $('a.edit-mode').trigger('click');
        }
    });
    
    //show or hide footer
    $(page + " #open-followup-container").on('scrollstop', function (){
        
        var footer = page + ' .preview-footer';
        if($(page + ' #icon-list-holder').isInViewport()){
            
            if($(footer).is(':visible') && ($(this).scrollTop() + $(this).innerHeight() < $(this)[0].scrollHeight)){
                $(footer).hide('fast');
            }
        }
        else{
            
            if($(footer).is(':hidden')){
                $(footer).show('fast');
            }
        }
    });
    
    //detect end of open container
    $(page + " #open-followup-container").scroll(function() {
        
        var footer = page + ' .preview-footer';
        if($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
            
            if($(footer).is(':hidden')){
                $(footer).show('fast');
            }
        }
    });
    
    //change panel heading on tab click
    $(page).on('click', 'a.ui-btn-inline', function(){
        
        //get the title
        var title = $(this).find('span.ui-btn-text').html();
        
        //change the title
        $(page + ' #tab-heading .panel-heading').html(title);
        
        //set the tab button
        setTabButton(page);
    });
    
    function switchTab(event){
        
        var current = null;
        var nextel = null;
        
        var direction = event.data.direction;        
        
        //find the active tab
        var body = $(page + " div.preview-body");
        var tab = body.find('#tabs li.ui-tabs-active');
        
        current = tab.children('a.ui-btn').attr('href');
        
        if(direction == 'left'){
            nextel = tab.next('li');
        }
        else{
            nextel = tab.prev('li');
        }
        
        if(tab.length > 0){
            var newtab = nextel.children('a.ui-btn').attr('href');
            var newel = $(page + ' div.preview-body #tabs a[href="' +newtab+ '"]');
            var index = newel.parent().index();
            
            //change tab
            $(page + " div.preview-body").find('#tabs').tabs("option", "active", index);
            
            //change title
            var title = newel.find('span.ui-btn-text').html();

            //change the title
            $(page + ' #tab-heading .panel-heading').html(title);

            //set the tab button
            setTabButton(page);
        }
    }
    
    //change tab on swipe
    $(page + ' div.preview-body').on('swipeleft',{direction: 'left'}, switchTab);
    $(page + ' div.preview-body').on('swiperight',{direction: 'right'}, switchTab);
});
</script>