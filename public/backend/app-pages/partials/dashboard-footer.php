<?php
use Jenga\App\Request\Url;
use Jenga\App\Request\Session;
?>
<script type="text/javascript">
    $(function(){
        
        var holder = "#footer-items-holder";
        var preloader = '<div style="padding-top: 60px; text-align: center;"> \
                            <img src="img/logo.gif" alt=""/> \
                         </div>';
        
        $('a.open-holder').on('click', function(event){
            
            event.preventDefault();
            
            //add search on to shrink footer
            $('#dashboard').removeClass('search-on').addClass('search-on');
            if($('#search-panel').is(':visible')){
               $('#search-panel').hide(); 
            }
            
            var href = $(this).attr('data-href');
            
            //open panel
            if($(holder).is(':hidden')){
                $(holder).openPanelUp('0px');
            }
            
            //add preloader
            $(holder).html(preloader);
            
            //load page
            $.ajax({
                url: href,
                method: "GET",
                error: function () {
                    $(document).trigger('connection:error');
                }
            }).done(function(data){
                $(holder).html(data);
            });
        });
        
        $('#load-profile').on('click', function(event){
            
            event.preventDefault();
            var holder = "#my-profile-holder";
            
            //add search on to shrink footer
            $('#dashboard').removeClass('search-on').addClass('search-on');
            if($('#search-panel').is(':visible')){
               $('#search-panel').hide(); 
            }
            
            //open panel
            if($(holder).is(':hidden')){
                $(holder).openPanelUp('0px');
            }
        });
        
        $('a.close-profile').on('click', function(){
        
            $('#dashboard').removeClass('search-on');
            $("#my-profile-holder").closePanelDown();
        });
    });
</script>
<div id="footer-items-holder" style="display: none;"></div>
<div id="my-profile-holder" style="display: none;">
    
    <div class="row">
        <div class="col text-right m-t-10 m-r-20 p-t-5">
            <a class="close-profile">
                <i class="ti-close" style="font-weight: bold;"></i>
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col text-center m-t-0">
            <div id="profile-icon" class="shadow">
                <i class="ti-user"></i>
            </div>
        </div>
    </div>
    <div class="row m-b-20">
        <div class="col text-center">
            //@@ TO-ADD add the agency
            <h4><?= $agency ?></h4>
        </div>
    </div>
    <div class="profile-links shadow">
        <div class="row">
            <div class="col">
                <a href="<?= Url::route('/user/logout/'.Session::id())?>" rel="external">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>
    
</div>
<div class="app-footer">
    <div class="container">
        <div class="row menu">
            <div id="MainPage" class="item col text-center p-t-10 p-r-0">
                <a href="<?= Url::link('/business/dashboard') ?>" rel="external">
                    <i class="ti-home"></i>
                    <span>Home</span>
                </a>
            </div>
            <div class="item col text-center p-t-10 p-l-0 inner-left">
                <a id="load-bill-page" rel="external"
                   data-href="<?= Url::link('/business/billing/show/summary/'.$this->user()->agenciesid) ?>" 
                   class="item open-holder">
                    <i class="ti-receipt"></i>
                    <span class="counter left"></span>
                    <span>Bills</span>
                </a>
            </div>
            <a id="btn-create-followup" href="#create-follow-up" data-transition="slidefade" class="item">
                <i class="fa fa-2x falist fa-bullhorn"></i><br/>
                <span class="m-l-5">Add</span>
            </a>
            <div class="item col text-center p-t-10 p-r-0 inner-right">
                <a id="load-notices-page" data-href="<?= Url::link('/business/notices/getall') ?>" rel="external" class="item open-holder">
                    <span class="counter right"></span>
                    <i class="ti-bell"></i>
                    <span>Alerts</span>
                </a>
            </div>
            <div class="item col text-center p-t-10 p-l-0">
                <a id="load-profile" href="#" data-rel="external" data-ajax="false" class="item">
                    <i class="ti-user"></i>
                    <span>You</span>
                </a>
            </div>
        </div>
    </div>
</div>
