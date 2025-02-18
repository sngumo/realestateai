<?php
use Jenga\App\Request\Url;
?>
<script type="text/javascript">    
$(function(){

    var holder = "#footer-items-holder";
    var preloader = '<div style="padding-top: 60px; text-align: center;"> \
                        <img src="img/logo.gif" alt=""/> \
                     </div>';

    $('#mobile-notifications ul li a').on('click', function(){
                    
        var id = $(this).attr('id');
        //var invid  = $(this).attr('data-invoice');
        var data = JSON.parse(localStorage.getItem('user-data'));
        
        //show header
        //$('header').show();
        
        $.ajax({
            url: "<?= Url::link('/api/business/notices/markasread/') ?>" + id + '/' + data.agenciesid,
            method: "GET"
        }).done(function(response){

            //remove the class
            var feedback = JSON.parse(response);
            if(feedback.status === 1){
                
                $('a#' + id).parent('li').removeClass('unread').addClass('read');
                //$('header').hide();
            }
        });
    });

    $('a.close-holder').on('click', function(){
        
        $('#dashboard').removeClass('search-on');
        $("#footer-items-holder").closePanelDown();
        
        //panel close event
        $(document).trigger('panel:close');
    });
    
    //view all link
    $('a#load-all').on('click', function(event){

        //set the agency name
        var local = localStorage;
        var userdata = JSON.parse(local.getItem('user-data'));

        event.preventDefault();

        //shrink main button
        $('#btn-create-followup').removeClass('expanded');

        //add search on to shrink footer
        $('#dashboard').removeClass('search-on').addClass('search-on');
        if($('#search-panel').is(':visible')){
           $('#search-panel').hide(); 
        }

        var href = $(this).attr('href');

        //open panel
        if($(holder).is(':hidden')){
            $(holder).openPanelUp('0px');
        }

        //add preloader
        $(holder).html(preloader);

        //load page
        $.ajax({
            url: href + userdata.agenciesid + '/all',
            method: "GET",
            tryCount : 0,
            retryLimit : 3,
            error: function (){

                this.tryCount++;
                if (this.tryCount <= this.retryLimit) {

                    //try again
                    $.ajax(this).done(function(data){
                        $(holder).html(data);
                    });
                    return;
                }   
                else{
                    $(document).trigger('connection:error');
                }

                return;
            }
        }).done(function(data){
            $(holder).html(data);
        });
    });
    
    //set all notifications to read
    $('#mobile-notifications ul li a').each(function(){
        if($(this).isInViewport()){

            //set to unread
            if($(this).parent('li').hasClass('unread')){
                $(this).trigger('click');
            }
        }
    });
});
</script>
<div id="mobile-notifications" class="bg-white p-t-0 p-l-0 p-r-0">
    <div class="row" id="alert-heading">
        <div class="col-10 text-left m-t-10 p-l-30">
            <h4 class="m-t-5"><i class="ti-bell"></i> Alerts</h4>
        </div>
        <div class="col-1 text-right m-t-10 m-r-0 p-t-15">
            <a class="close-holder">
                <i class="ti-close" style="font-weight: bold;"></i>
            </a>
        </div>
    </div>
    <div id="content">
        <!-- Tab panes -->
        <div class="tab-content p-0">
            <div role="tabpanel" class="tab-pane active" id="today">
                <ul class="list-w-title">
                    <?= $notices ?>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col text-center">
                <a href="<?= Url::link('/api/business/notices/getall/') ?>" class="btn btn-block" id="load-all">
                    View All
                </a>
            </div>
        </div>
    </div>
</div>