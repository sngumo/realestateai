<?php
use Jenga\App\Request\Url;
?>
<script type="text/javascript">
function runSearch(page){
;
    var holder = page + ' div.app-main .container';
    var searchstr = $('#searchbox').val();

    if(searchstr !== ''){
        
        //add preloader
        $(holder).html('<div style="padding-top: 60px; text-align: center;"> \
                        <img src="<?= TEMPLATE_URL ?>/backend/img/logo.gif" alt=""/> \
                        </div>');
        
        //hide the overlay
        $(page + ' #app-overlay').hide();
        $(page + ' #search-panel').show('fast');
        
        //scroll header into view
        $(page + ' div.app-header')[0].scrollIntoView();
        
        //perform the ajax
        $.ajax({
            method: "POST",
            url: "<?= Url::link('/business/invoices/search') ?>",
            data: {
                search: searchstr
            }
        }).done(function(response){
            $('#dashboard div.app-main .container').html(response);            
        });
    }
}    

$(function(){

    if(typeof $.mobile.activePage !== 'undefined'){
        var page = '#' + $.mobile.activePage.attr('id');
    }
    else{
        var page = '#dashboard';
    }
    
    $(page + ' div.app-mobile-logo').on('click', function(){
        
        //make sure filter isnt on
        if(!$(page).hasClass('filter-on')){
            
            //add search on
            $(page).removeClass('search-on').addClass('search-on');
            
            //show the overlay
            $(page + ' #app-overlay').show();

            //show the search form
            $(page + ' #search-panel').show('fast').addClass('active');

            //add focus to search input
            $(page + ' #search-panel input[name=searchfield]').focus();
            
            //get current slides and save to localDtorage
            if(localStorage.getItem('current-slides') === null){
                var currentslides = $(document).find(page + ' div.app-main .container').html();
                localStorage.setItem('current-slides', currentslides);
            }
            
            //add the hash
            localStorage.setItem('currentSearchHash', '#searchon');
            window.location.hash = '#searchon';
        }
    });
    
    //keypress on search
    $(document).on('keypress', '#searchbox', function(event){
        
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){ 
            runSearch(page);
        }        
    });
    
    //close button
    $(page + ' #search-panel .close-search').on('click', function(){
     
        //clear the search
        $(page + ' #searchbox').val('');
        
        //hide the overlay
        $(page + ' #app-overlay').hide();
     
        //show the search form
        $(page + ' #search-panel').hide('fast').removeClass('active');
        
        //remove search-on
        $(page).removeClass('search-on');
        
        //restore original slides
        var currentslides = localStorage.getItem('current-slides');
        if(currentslides !== null){
            $(page + ' div.app-main .container').html(currentslides);
        }        
        
        //remove the hash
        localStorage.removeItem('currentSearchHash');
        window.location.hash = '';
    });
    
    //close on overlay click
    $(document).on('overlay:click', function(event){
        
        //get the current page
        var page =  '#' + event.page;
        
        //trigger click
        if(page === '#dashboard'){
            $(page + ' #search-panel .close-search').trigger('click');
        }
    });
    
    //show when out of viewport
    $(page + ' #dashboard-container').on('scroll', function (){
        
        var bar = $(page + ' div.filter-bar');
        
        if(!bar.isInViewport() && localStorage.getItem('currentFilterHash') == null){
            $(page + ' #search-panel').show();
        }
        else{
            if($(page + ' #search-panel').is(':visible') 
                    && (window.location.hash === '' || window.location.hash === '#dashboard')
                    && localStorage.getItem('currentSearchHash') === null){
                
                $(page + ' #search-panel').hide();
            }
        }
    });
    
    //load search panel on Search FolloUps click
    $(page + ' #panel-tag').on('click', function(){
        $(page + ' div.app-mobile-logo').trigger('click');
    });
});
</script>
<div id="search-panel" class="panel bg-white shadow" style="display: none;">
    <div id="panel-tag" class="row">
        <div class="col">
            <span class="title">Search FollowUps</span>
        </div>
        <div class="col-2 text-right">
            <span class="search-btn">
                <i class="fa fa-search" aria-hidden="true"></i>           
            </span>
        </div>
    </div>
    <div id="panel-input" class="row">
        <div class="col-2 text-right forward">
            <span class="search-btn">
                <i class="fa fa-search" aria-hidden="true"></i>
            </span>
        </div>
        <div class="col p-l-0">
            <input id="searchbox" name="searchfield" type="text" value="" placeholder="Customer or Product" data-role="none" />
        </div>
        <div class="col-2 text-right back">
            <span class="search-btn close-search">
                <i class="fa fa-close"></i>                
            </span>
        </div>
    </div>
</div>
