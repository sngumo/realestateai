<?php

use Jenga\App\Request\Url;
?>
<script type="text/javascript">
    jQuery.fn.scrollCenter = function (elem, speed) {

        // this = #timepicker
        // elem = .active

        var active = jQuery(this).find(elem); // find the active element
        //var activeWidth = active.width(); // get active width
        var activeWidth = active.width() / 2; // get active width center

        //alert(activeWidth)

        //var pos = jQuery('#timepicker .active').position().left; //get left position of active li
        // var pos = jQuery(elem).position().left; //get left position of active li
        //var pos = jQuery(this).find(elem).position().left; //get left position of active li
        var pos = active.position().left + activeWidth; //get left position of active li + center position
        var elpos = jQuery(this).scrollLeft(); // get current scroll position
        var elW = jQuery(this).width(); //get div width
        //var divwidth = jQuery(elem).width(); //get div width
        pos = pos + elpos - elW / 2; // for center position if you want adjust then change this

        jQuery(this).animate({
            scrollLeft: pos
        }, speed == undefined ? 1000 : speed);
        return this;
    };

    // http://podzic.com/wp-content/plugins/podzic/include/js/podzic.js
    jQuery.fn.scrollCenterORI = function (elem, speed) {
        jQuery(this).animate({
            scrollLeft: jQuery(this).scrollLeft() - jQuery(this).offset().left + jQuery(elem).offset().left
        }, speed == undefined ? 1000 : speed);
        return this;
    };

    function openPreview(event = null, id = null) {

        if(event != null){
            var page = event.data.page;
        }
        else{
            var page = '#dashboard';
        }
        
        if(id == null){
            var id = $(this).attr('data-followup-id');
        }
        
        if($(document).find('#contacts-' + id).length > 0){
            
            var contacts = JSON.parse($(document).find('#contacts-' + id).val());

            //map value to element
            for (var key in contacts) {
                $(page + " div#preview-box [data-map-to=" + key + "]").html(contacts[key]);
            }

            //set the followup id
            $(page + " #more-details-btn a").attr("data-followup-id", id);
            $(page + " #customer-row a.open-followup").attr("data-followup-id", id);

            $(page + " div#preview-box").removeClass().addClass(contacts.status);

            //show overlay
            $(page + " .overlay").show();

            //show preview box
            $(page + " div#preview-box").show("fast");
            
            //check the search panel
            var search = $(page + ' #search-panel');
            if(search.is(':visible') && $(page + ' #search-panel > #panel-input').is(':hidden')){
                search.hide();
            }

            //add preview to hash
            localStorage.setItem('currentPreviewHash', '#openpreview-' + id);
            window.location.hash = '#openpreview-' + id;
        }
    };
    
    function closePreview(event = null) {
        
        if(event != null ){  
            
            if(typeof event.data !== 'undefined'){
                var page = event.data.page;
            }
            else{
                var page = event.page;
            }
            
            closeAction(page);
        }
        else{
            var page = '#dashboard';
            var newHash = window.location.hash;
            var currentHash = '';
            
            if (localStorage.getItem("currentPreviewHash") !== null) {
                currentHash = localStorage.getItem("currentPreviewHash");
            }
            
            //check hash lengths
            if(newHash.length < currentHash.length){
                closeAction(page);
            }
        }   
    }
    
    //on close event force close
    function closeAction(page){

        //hide overlay
        $(page + ' div.overlay').hide();

        //hide preview box
        $(page + ' div#preview-box').hide("fast");
        $(page + ' div#preview-box div.notice').html("");

        //remove hash from history
        window.location.hash = "";
        localStorage.removeItem('currentHash');
    }
    
    function openFollowUp(id = null){

        if(id == null || typeof id == 'object'){
            id = $(this).attr('data-followup-id');
        }
        
        var contact = $('#contacts-' + id);
        var page = '#follow-up-opened';
        
        if(contact.length === 0){
            return false;
        }
        
        var contacts = JSON.parse(contact.val());

        //set to local storage
        localStorage.setItem('latest-followup', id);

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
        for (var key in contacts) {
            $(page + " [data-map-to=" + key + "]").html(contacts[key]);
        }

        //change page
        $.mobile.navigate(page, {
            transition: "slidefade"
        });

        //hide overlay
        $("#dashboard .overlay").hide();

        //hide preview box
        $("#dashboard div#preview-box").hide("fast");
        $('#dashboard div#preview-box div.notice').html("");
    }
    
    function openPreviewIfHashPresent(page){
            
        //detect url hash changes
        var hash = window.location.hash;
                        
        if(hash !== ''){

            //check if preview box is visible
            if(!$(page + " div.preview-body").is(':visible')){
                
                var hashsplit = hash.split("-");
                if(hashsplit[0] == '#openpreview'){
                    openPreview(null, hashsplit[1]);
                }
            }
        }     
    }
    
    function openFilterIfHashPresent(page){
            
        //detect url hash changes
        var hash = window.location.hash;
        if(hash !== ''){

            //check if page has filter-on class
            if(!$(page).hasClass('filter-on')){
                
                var hashsplit = hash.split("-");
                if(hashsplit[0] == '#filter'){
                     
                    var href = $(page + ' div.filter-bar a[data-filter=' +hashsplit[1]+ ']').attr('href');
                    $(page + ' div.filter-bar a').trigger('click',[href, hashsplit[1]]);
                }
            }
        }     
    }
    
    function garbageCollectPage(){
        
        //remove preview hash
        if(localStorage.getItem('currentPreviewHash') !== null){
            localStorage.removeItem('currentPreviewHash');
        }
        
        //remove search hash
        if(localStorage.getItem('currentSearchHash') !== null){
            localStorage.removeItem('currentSearchHash');
        }
        
        //remove search hash
        if(localStorage.getItem('currentFilterHash') !== null){
            localStorage.removeItem('currentFilterHash');
        }
    }

    $(function () {
    
        //check if in mobile environment
        if ($('body').attr('environment') === 'mobile') {
            
            var page = '#dashboard';
            
            //garbage collect dashboard page
            garbageCollectPage(); //by default
            
            $(page).on( "pageshow", function() {
                garbageCollectPage();
            });
            
            //open preview if hash present
            openPreviewIfHashPresent(page);
            
            //clear current slides in localStorage
            if(localStorage.getItem('current-slides') !== null){
                localStorage.removeItem('current-slides');
            }
            
            //close preview on back button
            if (window.history && window.history.pushState) {
                $(window).on('popstate', function(){
                    
                    //check if page is dashboard
                    if('#' + $.mobile.activePage.attr('id') === page){
                        
                        var newHash = window.location.hash;
                        var currentHash = '';
                            
                        //close preview if back button is clicked
                        if($(page + ' div#preview-box').is(':visible')){
                            
                            //close preview
                            if($('#followup-tools-panel').is(':hidden')){
                                closePreview();
                            }
                        }
                        else if($(page).hasClass('filter-on')){

                            if (localStorage.getItem("currentFilterHash") !== null) {
                                currentHash = localStorage.getItem("currentFilterHash");
                            }

                            //check hash lengths
                            if(newHash.length < currentHash.length){
                                $(page + ' #filter-indicator .close-filter').trigger('click');
                            }
                        }
                        else if($(page).hasClass('search-on')){

                            if (localStorage.getItem("currentSearchHash") !== null) {
                                currentHash = localStorage.getItem("currentSearchHash");
                            }

                            //check hash lengths
                            if(newHash.length < currentHash.length){
                                $(page + ' #search-panel .close-search').trigger('click');
                            }
                        }
                    }
                });
            }
            
            //open preview box
            $(document).on('click', page + ' div.follow-up-panel', {page: page}, openPreview);

            //close preview
            $(page + ' .close-preview').on('click', {page: page}, closePreview);
            $(document).on('overlay:click', function(event){
                
                //get the current page
                var page =  '#' + event.page;
                
                if(page === '#dashboard'){
                    closePreview(event);
                }
            });

            //get contact details on click
            $('a.open-followup').on('click', openFollowUp);
            
            //fully open followup on swipeleft
            $(document).on('swipeleft', page + ' div.follow-up-panel', openFollowUp);

            //filter toolbar
            $(page + ' div.filter-bar a').on('click', function (event, href = '', filter = '') {
                
                var triggered  = true;
                event.preventDefault();

                //get the href
                if(href === ''){
                    
                    //set to false
                    triggered = false;
                    
                    //set href
                    href = $(this).attr('href');
                }
                
                //get the filter
                if(filter === ''){
                    filter = $(this).attr('data-filter');
                }

                //add "selected" class
                $(page + ' div.filter-bar a').removeClass();
                
                if(triggered){
                    $(page + ' div.filter-bar a[data-filter='+ filter +']').removeClass().addClass('selected');
                }
                else{
                    $(this).removeClass().addClass('selected');
                }
                
                //scroll to center
                $(page + " div.filter-bar").scrollCenter(".selected", 300);
                
                //hide top section
                $(page + ' div.app-mobile-logo').parents('div.container').hide('fast');
                $(page).removeClass('filter-on').addClass('filter-on');
                
                //filter indicator modifications
                $(page + ' #filter-indicator').show('fast');
                $(page + ' #filter-indicator .row').removeClass()
                            .addClass('row')
                            .addClass(filter);
                
                $(page + ' #filter-indicator .filter-type').html(filter);

                //get current slides and save to localDtorage
                if(localStorage.getItem('current-slides') === null){
                    var currentslides = $(page + ' div.app-main .container').html();
                    localStorage.setItem('current-slides', currentslides);
                }

                //add preloader
                $(page + ' div.app-main .container')
                        .html('<div style="padding-top: 60px; text-align: center;"> \
                                    <img src="<?= TEMPLATE_URL ?>/backend/img/logo.gif" alt=""/> \
                                </div>');

                //add filter hash
                localStorage.setItem('currentFilterHash', '#filter-' + filter);
                window.location.hash = '#filter-' + filter;

                //do the ajax operation
                $.ajax({
                    method: "GET",
                    url: href,
                    error: function () {
                        $(document).trigger('connection:error');
                    }
                }).done(function (response) {
                    $(page + ' div.app-main .container').html(response);
                });
            });
            
            //open filter if hash present
            openFilterIfHashPresent(page);
            
            //close search filter
            $(page + ' #filter-indicator .close-filter').on('click', function(event){
                
                //remove selected from filter link
                $(page + ' div.filter-bar a').removeClass('selected');
                
                //show top section
                $(page + ' div.app-mobile-logo').parents('div.container').show('fast');
                
                //remove filter-on for page
                $(page).removeClass('filter-on');
                
                //filter indicator modifications
                $(page + ' #filter-indicator').hide('fast');
                
                //restore original slides
                var currentslides = localStorage.getItem('current-slides');
                $(page + ' div.app-main .container').html(currentslides);
                
                //scroll to top
                $(page + ' div.app-mobile-logo')[0].scrollIntoView();
                
                //scroll to center
                $(page + " div.filter-bar").scrollCenter("#filter-open", 300);
                
                //remove filter hash
                localStorage.removeItem('currentFilterHash');
                window.location.hash = '';
            });
            
            //fic filter indicator
            $(page + ' #dashboard-container').on('scroll', function (){
                
                //fix filter-indicator if out of viewport
                var bar = $(page + ' div.filter-bar');
                var indicator = $(page + ' #filter-indicator');

                if(bar.isInViewport() === false){

                    if(indicator.is(':visible')){
                        //add fixed
                        indicator.removeClass('fixed').addClass('fixed');
                    }
                }
                else{
                    
                    if(indicator.is(':visible')){
                        //remove fixed
                        if(indicator.hasClass('fixed')){
                            indicator.removeClass('fixed');
                        }
                    }
                }                
            });
        }
    });
</script>