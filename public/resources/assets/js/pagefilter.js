$(function(){
    
    //hide advanced div
    $('div.advanced').hide();
    
    //show hide
    $('a.btn-show-hide').on('click', function(){
        $('div.advanced').toggle('fast');
    });
    
    //dynamic filter select input
    $("input.dynamic-select2").each(function(){
        
        var $element = $(this);
        var placeholder = $element.attr('placeholder');
        var destination = $element.attr('data-url');

        //attach event to dynamic select2
        $("input.dynamic-select2").select2({
            placeholder: placeholder,
            allowClear: true,
            maximumSelectionSize: 1, 
            minimumInputLength: 1,
            ajax: {
                url: destination,
                data: function (term) { // page is the one-based page number tracked by Select2
                    return {
                        search: term, //search term
                        limit: 50
                    };
                },
                results: function (data) {
                    return data;
                }
              }
        });
    });
    
    //filter button
    $("button[name='filterbtn']").on('click', function(event){
        event.preventDefault();
        
        //validate form
        var ok = false;
        var inputs = new Array();
        $(".filterinput").each(function(index, value){
            
            if($(this).val() !== ''){
                inputs.push(index);
            }
        });
        
        //check if all inputs are empty
        if(inputs.length > 0){
            ok = true;
        }
        
        if(ok === false) {
            $('#filter-message').show();
            $('#filter-message div.message').html(ok ? '' : 'You must correctly fill at least one of the fields');
              return;
        };
        
        //get table and preload
        var table = $('#filter-table').val();
        var preload = $('div.preload').html();
        var url = $(this).attr('data-url');
        
        //add preloader
        $('#' + table).html(preload);
        var formdata = $('#filterform').serializeArray();
        
        //statrt ajax
        $.ajax({
            url: url,
            method: "POST",
            data: formdata,
            success: function(response){
                $('#' + table).html(response);
            }
        });
    });
});

