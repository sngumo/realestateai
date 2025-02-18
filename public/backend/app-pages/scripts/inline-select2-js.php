<?php
use Jenga\App\Request\Url;
?>
<script type="text/javascript">
function generatePaymentList(data){

    var html = '<ul class="inline-select2-list">';    
    var list = JSON.parse(data);

    for (i = 0; i < list.length; i++) {

        var item = list[i];

        html += '<li class="item">';
        //html += '<input type="hidden" id="item_json_'+i+'" name="item_json_'+i+'" value="' +data+ '" />';

        html += '<div class="row">';
            html += '<div class="col">';

                html += '<div class="row">';
                    html += '<div class="col name" data-inline-val="' +item.name.toLowerCase()+ '">';

                    html += item.name;

                    html += '</div>';
                html += '</div>';

                html += '<div class="row">';
                    html += '<div class="col description" data-inline-val=\'' + item.price + '\'>';

                    html += 'Price: ' + item.code +' '+ item.price;

                    html += '</div>';
                html += '</div>';

            html += '</div>';
        html += '</div>';

        html += '</li>';
    }

    html += '</ul>';

    return html;
};

function generateHTMLList(data){

    var html = '<ul class="inline-select2-list">';    
    var list = JSON.parse(data);

    for (i = 0; i < list.length; i++) {

        var item = list[i];
        var description = item.description;

        html += '<li class="item">';
        //html += '<input type="hidden" id="item_json_'+i+'" name="item_json_'+i+'" value="' +data+ '" />';

        html += '<div class="row">';
            html += '<div class="col-2 p-0 p-l-5 text-center">';

                html += '<div class="icon">';
                    html += item.icon.toUpperCase();
                html += '</div>';

            html += '</div>';
            html += '<div class="col">';

                html += '<div class="row">';
                    html += '<div class="col name" data-inline-val="' +item.name.toLowerCase()+ '">';

                    html += item.name;

                    html += '</div>';
                html += '</div>';

                html += '<div class="row">';
                    html += '<div class="col description" data-inline-val=\'' +JSON.stringify(description)+ '\'>';

                    html += 'Tel: ' + description.mobile + ', Email: ' + (description.email !== null ? description.email : '');

                    html += '</div>';
                html += '</div>';

            html += '</div>';
        html += '</div>';

        html += '</li>';
    }

    html += '</ul>';

    return html;
};

function filterCustomersList(searchstr, inlineval){

    var found = false;
    
    //check if its the description 
    if(isJson(inlineval)){

        var inline = JSON.parse(inlineval);
        var mobile = inline.mobile;
        var email = inline.email;

        //check mobile
        if(mobile !== null){
            if(mobile.search(searchstr) !== -1){
                found = true;
            }
        }

        //check email
        if(email !== null){
            if(email.search(searchstr) !== -1){
                found = true;
            }
        }
    }
    else{

        //search the rest
        if(inlineval.search(searchstr) !== -1){
            found = true;
        }
    } 
    
    return found;
};

function filterPaymentsList(){
    console.log('here');
};
    

$.fn.inlineSelect2 = function(options){
    
    var id = $(this).attr('id');
    var page = options.page;
    var holder = page + ' #' + id + '_holder div.list';
    var mode = localStorage.getItem('create:followup:mode');
    
    if(mode === 'new' || mode === null){
            
        //check if container is hidden
        var container = $(page + ' .inline-select2-container');
        container.show();
        
        //hide list holder
        $(page + ' #customer-list-holder').hide();
    }
    
    //set preloader
    $(holder).html('<div class="text-center">' + options.preloader + '</div>');
    
    //load inline list
    $.ajax({
        url: options.source,
        method: "GET",
        cache: false,
        async: true,
        error: function () {
            $(document).trigger('connection:error');
        }
    }).done(function(data){
        
        if(data !== 'not-found'){
            
            var handler = options.handler;
            var htmlstring = window[handler](data);
            $(holder).html(htmlstring);
        }
        else{
            
            //start event
            var event = jQuery.Event('select2:not-found');
            
            //throw event
            $(document).trigger(event);
        }
    });
    
    //search list
    $(this).on('keydown', {options: options}, function(event){
        
        var searchstr = this.value;
        var options = event.data.options;
        
        $(holder + ' li.item').each(function(){
                    
            var found  = false;
            var children = $(this).find('[data-inline-val]');
            var filter = options.filter;
            
            if(typeof filter !== 'undefined'){
                
                children.each(function(){

                    if(searchstr != ''){
    
                        var inlineval = $(this).attr('data-inline-val');  
                        searchstr = searchstr.toLowerCase();
                        
                        //found = window[filter](searchstr, inlineval); 

                        if(filter == 'filterCustomersList'){
                            
                            //check if its the description 
                            if(isJson(inlineval)){

                                var inline = JSON.parse(inlineval);
                                var mobile = inline.mobile;
                                var email = inline.email;

                                //check mobile
                                if(mobile !== null){
                                    if(mobile.search(searchstr) !== -1){
                                        found = true;
                                    }
                                }

                                //check email
                                if(email !== null){
                                    if(email.search(searchstr) !== -1){
                                        found = true;
                                    }
                                }
                            }
                            else{

                                //search the rest
                                if(inlineval.search(searchstr) !== -1){
                                    found = true;
                                }
                            } 
                        }
                        else if(filter == 'filterPaymentsList'){
                            
                            //search the inline value
                            if(inlineval.search(searchstr) !== -1){
                                found = true;
                            }
                        }
                    }
                });

                //if found
                if(found === false){
                    
                    if($(this).is(':visible')){
                        $(this).hide();
                    }
                }
            }
        });
        
        //show all by default
        if(searchstr == '' || searchstr == ' '){
            $(holder + ' li.item').show();
        }
    });
};

//throw select2 event
$(document).on('click', '.inline-select2-container li.item', function(){
            
    var select2vals = [];
    
    var page = $.mobile.activePage.attr('id');
    var children = $(this).find('[data-inline-val]');
    var id = $(this).parents('.inline-select2-container').attr('id');
    
    children.each(function(index){
        select2vals[index] = $(this).attr('data-inline-val');
    });
    
    //start event
    var event = jQuery.Event('select2:selected');
        
    //set the current page
    event.page = page;
    
    //set the current id
    event.id = id;
    
    //set the select2 values
    event.select2vals = select2vals;

    //throw event
    $(document).trigger(event);
});
</script>