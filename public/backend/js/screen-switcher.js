/**
 * Switches the screen from desktop to mobile depending on the width
 * @type type
 */

function showHideElemenets(){
    
    var width = $(window).width();
    
    //set desktop viewport
    if(width > 1024){
        $('body').attr('environment', 'desktop');

        //show desktop
        $('[env="desktop"]').show();

        //hide the tablet and mobile elements
        $('[env="mobile"]').hide();
        $('[env="tablet"]').hide();
    }
    else{

        //set the mobile viewport
        $('body').attr('environment', 'mobile');

        //show desktop
        $('[env="mobile"]').show();

        //hide the desktop and mobile elements
        $('[env="desktop"]').hide();
        $('[env="tablet"]').hide();
    }
}

//window resize code
function windowResize(func){

    //show/hide page elements
    func();
    
    //trigger window resize event
    $(document).trigger('window.resize');
}


$(function(){
    
    //the responsiveness code
    windowResize(showHideElemenets);
    
    //recheck window size on resize
//    $(window).resize(function(){
//        windowResize(showHideElemenets);
//    });
});
    