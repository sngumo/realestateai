<script type="text/javascript">
    
function closeToolPanel(){
    
    $('#followup-tools-panel').closePanelDown(); 
    
    var preview = '';
    if('#' + $.mobile.activePage.attr('id') === '#dashboard'){
        
        if(localStorage.getItem('currentPreviewHash') !== null){
            preview = localStorage.getItem('currentPreviewHash');
        }
    }
    else if('#' + $.mobile.activePage.attr('id') === '#follow-up-opened'){
        preview = '#follow-up-opened';
    }

    //remove hash from history
    localStorage.removeItem('currentToolHash');
    window.location.hash = preview;    
};
    
$(function(){
    
    var panel = '#followup-tools-panel';
    
    //close preview on back button
    if (window.history && window.history.pushState) {
        $(window).on('popstate', function(){
            
            if(localStorage.getItem('currentToolHash') == '#tool-open'){
                closeToolPanel();
            }
        });
    }
    
    //close panel
    $(document).on('click', panel + ' a.close-panel', closeToolPanel);
    
    //open panel
    $(document).on('click', 'div.tools a.tool', function(){
        
        //open panel
        if($(this).hasClass('disabled')){
            return false;
        }
        
        //open panel up
        $(panel).openPanelUp('3vh'); 
        
        //set hash
        window.location.hash = '#tool-open';
        localStorage.setItem('currentToolHash', '#tool-open');
        
        //add title
        var title = $(this).attr('data-panel-title');
        $(panel + ' div.heading div.title').html(title);
        
        //add preloader
        var content = panel + ' div.content';
        $(content).html('<div style="padding-top: 60px; text-align: center;"> \
                                    <img src="<?= TEMPLATE_URL ?>/backend/img/logo.gif" alt=""/> \
                                </div>');
                                        
        $.ajax({
            method: "GET",
            url: $(this).attr('data-panel-href')
        }).done(function (response) {
            $(content).html(response);
        });
    });    
    
    $(document).on('click', 'a.main-bottom-tool', function(){
        
        //open panel
        $(panel).openPanelUp('3vh'); 
        
        //set hash
        window.location.hash = '#tool-open';
        localStorage.setItem('currentToolHash', '#tool-open');
        
        //add title
        var title = $(this).attr('data-panel-title');
        $(panel + ' div.heading div.title').html(title);
        
        //add preloader
        var content = panel + ' div.content';
        $(content).html('<div style="padding-top: 60px; text-align: center;"> \
                                    <img src="<?= TEMPLATE_URL ?>/backend/img/logo.gif" alt=""/> \
                                </div>');
                                        
        $.ajax({
            method: "GET",
            url: $(this).attr('data-panel-href'),
            error: function () {
                $(document).trigger('connection:error');
            }
        }).done(function (response) {
            $(content).html(response);
        });
    });   
    
});
</script>
<div id="followup-tools-panel" style="display: none;">
    <div class="heading">
        <div class="row">
            <div class="col text-left m-t-10 p-l-20">
                <div class="title p-t-5"></div>
            </div>
            <div class="col-1 text-right m-t-10 m-r-20 p-t-5">
                <a class="close-panel ui-link">
                    <i class="ti-close" style="font-weight: bold;"></i>
                </a>
            </div>
        </div>
        <hr>
    </div>
    <div class="content p-0"></div>
</div>
