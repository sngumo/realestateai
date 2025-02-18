$(function(){
    
    var modal = '#addeditmodal';
    var formid = $('div[role="tabpanel"]').attr('id');
    
    //load initial tab
    var preload = $('#preloader').html();
    
    //get active link
    var href = $('#'+formid+' li.active a').attr('href');
    
    //get active tab
    var tab = $('#'+formid+' li.active a').attr('aria-controls');
    
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
    $.ajax({
        url: href,
        method: "GET"
    })
    .done(function(data){
        $('div#' + tab).html(data);
    });
    
    //add click event handler
    $('ul.nav-tabs a').on('click', {
            preloader: preload,
            modal: modal
        },function(event){
        
        //remove previuos active link and tab
        $('ul.nav-tabs li.active').removeClass('active');
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
        
        $(this).parent('li').addClass('active');
        $('div#' + tab).addClass('active').html(event.data.preloader);
        
        $.ajax({
            url: href,
            method: "GET"
        })
        .done(function(data){
            $('div#' + tab).addClass('active').html(data);
        });
    });
});